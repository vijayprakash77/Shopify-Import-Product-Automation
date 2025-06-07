<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessShopifyImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uploadId;
    
    // Job timeout in seconds (2 hours)
    public $timeout = 7200;
    
    // Number of times the job may be attempted
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct($uploadId)
    {
        $this->uploadId = $uploadId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if upload exists and is not paused
            $upload = DB::table('uploads')->where('id', $this->uploadId)->first();
            
            if (!$upload) {
                Log::error("Upload not found: {$this->uploadId}");
                return;
            }

            if ($upload->status === 'paused') {
                Log::info("Upload is paused: {$this->uploadId}");
                return;
            }

            // Get pending products
            $products = DB::table('products')
                ->where('upload_id', $this->uploadId)
                ->where('import_status', 'pending')
                ->get();

            if ($products->isEmpty()) {
                // Mark upload as completed
                DB::table('uploads')
                    ->where('id', $this->uploadId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'updated_at' => now()
                    ]);
                return;
            }

            // Process each product
            foreach ($products as $product) {
                // Check if upload is paused
                $currentUpload = DB::table('uploads')->where('id', $this->uploadId)->first();
                if ($currentUpload->status === 'paused') {
                    Log::info("Upload paused during processing: {$this->uploadId}");
                    break;
                }

                $this->processProduct($product);
                
                // Update processed count
                $this->updateUploadProgress();
                
                // Small delay to prevent API rate limiting
                usleep(500000); // 0.5 seconds
            }

            // Check if all products are processed
            $remainingProducts = DB::table('products')
                ->where('upload_id', $this->uploadId)
                ->where('import_status', 'pending')
                ->count();

            if ($remainingProducts === 0) {
                DB::table('uploads')
                    ->where('id', $this->uploadId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'updated_at' => now()
                    ]);
            }

        } catch (Exception $e) {
            Log::error("Shopify import job failed: " . $e->getMessage());
            
            // Mark upload as failed
            DB::table('uploads')
                ->where('id', $this->uploadId)
                ->update([
                    'status' => 'failed',
                    'errors' => $e->getMessage(),
                    'updated_at' => now()
                ]);
                
            throw $e;
        }
    }

    /**
     * Process individual product - Check existence before create/update
     */
    private function processProduct($product)
    {
        try {
            $productId = null;
            $variantId = null;
            $isNewProduct = false;

            // Check if product already exists in our database
            if (!empty($product->shopify_product_id)) {
                $productId = $product->shopify_product_id;
                Log::info("Product exists in database: {$productId}");
            } else {
                // Check if product exists in Shopify by handle or title
                $existingProduct = $this->findExistingProduct($product);
                
                if ($existingProduct) {
                    $productId = $existingProduct['id'];
                    Log::info("Product found in Shopify: {$productId}");
                    
                    // Update our database with the found product ID
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['shopify_product_id' => $productId]);
                } else {
                    $isNewProduct = true;
                }
            }

            // STEP 1 & 2: Create or Update Product
            if ($isNewProduct) {
                $productResponse = $this->createShopifyProduct($product);
                if (!$productResponse['success']) {
                    throw new Exception("Product creation failed: " . $productResponse['error']);
                }
                $productId = $productResponse['product_id'];
                Log::info("New product created: {$productId}");
            } else {
                $updateResponse = $this->updateShopifyProduct($productId, $product);
                if (!$updateResponse['success']) {
                    Log::warning("Product update failed: " . $updateResponse['error']);
                }
                Log::info("Product updated: {$productId}");
            }

            // STEP 3: Handle Variant - Get default variant and update it
            if ($this->hasVariantData($product)) {
                // Check if variant already exists in our database
                if (!empty($product->shopify_variant_id)) {
                    $variantId = $product->shopify_variant_id;
                    Log::info("Variant exists in database: {$variantId}");
                } else {
                    // Get the default variant (first variant) of the product
                    $defaultVariant = $this->getDefaultVariant($productId);
                    
                    if ($defaultVariant) {
                        $variantId = $defaultVariant['id'];
                        Log::info("Default variant found: {$variantId}");
                        
                        // Update our database with the found variant ID
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update(['shopify_variant_id' => $variantId]);
                    } else {
                        Log::warning("No default variant found for product: {$productId}");
                    }
                }

                // Update variant with product details
                if ($variantId) {
                    $updateResponse = $this->updateShopifyVariant($variantId, $product);
                    if (!$updateResponse['success']) {
                        Log::warning("Variant update failed: " . $updateResponse['error']);
                    } else {
                        Log::info("Default variant updated: {$variantId}");
                    }
                }
            }

            // STEP 4: Add product to collection (only for new products)
            if ($isNewProduct) {
                $this->addProductToCollection($productId);
                Log::info("Product added to collection");
            }

            // Handle image (create or update)
            if (!empty($product->image_src)) {
                $this->addOrUpdateProductImage($productId, $product->image_src, $product->image_alt_text);
            }

            // Mark as successful
            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'shopify_product_id' => $productId,
                    'shopify_variant_id' => $variantId,
                    'import_status' => 'success',
                    'import_error' => null,
                    'updated_at' => now()
                ]);
                
            Log::info("Product processed successfully: {$product->handle}");
            
        } catch (Exception $e) {
            // Mark product as failed
            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'import_status' => 'failed',
                    'import_error' => $e->getMessage(),
                    'updated_at' => now()
                ]);
                
            Log::error("Product processing failed: {$product->handle} - {$e->getMessage()}");
        }
    }

    /**
     * Find existing product in Shopify by handle or title
     */
    private function findExistingProduct($product)
    {
        try {
            $handle = $this->generateHandle($product->title);
            
            $query = sprintf(
                'query {
                    products(first: 1, query: "handle:%s OR title:%s") {
                        edges {
                            node {
                                id
                                handle
                                title
                            }
                        }
                    }
                }',
                addslashes($handle),
                addslashes($product->title)
            );

            $response = $this->executeGraphQLQuery($query);
            
            if (isset($response['data']['products']['edges'][0]['node'])) {
                return $response['data']['products']['edges'][0]['node'];
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error("Error finding existing product: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the default (first) variant of a product
     */
    private function getDefaultVariant($productId)
    {
        try {
            $query = sprintf(
                'query {
                    product(id: "%s") {
                        variants(first: 1) {
                            edges {
                                node {
                                    id
                                    title
                                    sku
                                }
                            }
                        }
                    }
                }',
                $productId
            );

            $response = $this->executeGraphQLQuery($query);
            
            if (isset($response['data']['product']['variants']['edges'][0]['node'])) {
                Log::info("Found default variant for product");
                return $response['data']['product']['variants']['edges'][0]['node'];
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error("Error getting default variant: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find existing "variant1" variant in a product - DEPRECATED
     * Kept for backward compatibility
     */
    private function findVariant1($productId)
    {
        // This method is now deprecated - use getDefaultVariant instead
        return $this->getDefaultVariant($productId);
    }

    /**
     * Create new "variant1" variant in a product - DEPRECATED
     * This method is no longer used as we update the default variant instead
     */
    private function createVariant1($productId, $product)
    {
        Log::info("createVariant1 is deprecated - using default variant update instead");
        return [
            'success' => false,
            'error' => 'Method deprecated - use default variant update instead'
        ];
    }

    /**
     * Find existing variant in Shopify by SKU (kept for backward compatibility)
     */
    private function findExistingVariant($productId, $product)
    {
        return $this->getDefaultVariant($productId);
    }

    /**
     * Update existing Shopify product
     */
    private function updateShopifyProduct($productId, $product)
    {
        try {
            $tags = '';
            if (!empty($product->tags)) {
                $tagsList = array_map(function($tag) {
                    return '"' . addslashes(trim($tag)) . '"';
                }, explode(',', $product->tags));
                $tags = 'tags: [' . implode(',', $tagsList) . ']';
            }

            $mutation = sprintf(
                'mutation {
                    productUpdate(input: {
                        id: "%s",
                        title: "%s",
                        descriptionHtml: "%s",
                        vendor: "%s",
                        productType: "%s",
                        published: %s
                        %s
                    }) {
                        product {
                            id
                        }
                        userErrors {
                            field
                            message
                        }
                    }
                }',
                $productId,
                addslashes($product->title),
                addslashes($product->body_html ?: ''),
                addslashes($product->vendor ?: ''),
                addslashes($product->product_type ?: ''),
                $product->published === 'true' ? 'true' : 'false',
                $tags ? ', ' . $tags : ''
            );

            $response = $this->executeGraphQLQuery($mutation);

            if (isset($response['data']['productUpdate']['userErrors']) && !empty($response['data']['productUpdate']['userErrors'])) {
                throw new Exception("Product Update Errors: " . json_encode($response['data']['productUpdate']['userErrors']));
            }

            return ['success' => true];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update existing Shopify variant (now updates default variant)
     */
   private function updateShopifyVariant($variantId, $product)
{
    try {
        $variantData = $this->prepareVariantData($product);

        // Prepare inventory quantities in the correct format
        $inventoryQuantities = '';
        if (!empty($product->variant_inventory_qty) && is_numeric($product->variant_inventory_qty)) {
            $locationId = $this->getPrimaryLocationId();
            if ($locationId) {
                $inventoryQuantities = sprintf(
                    'inventoryQuantities: [{
                        availableQuantity: %d,
                        locationId: "%s"
                    }]',
                    (int)$product->variant_inventory_qty,
                    $locationId
                );
            }
        }

        $mutation = sprintf(
            'mutation {
                productVariantUpdate(input: {
                    id: "%s",
                    sku: "%s",
                    price: "%s",
                    compareAtPrice: %s,
                    requiresShipping: %s,
                    taxable: %s,
                    inventoryPolicy: %s,
                    weight: %s,
                    weightUnit: %s
                    %s
                }) {
                    productVariant {
                        id
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }',
            $variantId,
            addslashes($variantData['sku']),
            $variantData['price'],
            $variantData['compareAtPrice'] ? '"' . $variantData['compareAtPrice'] . '"' : 'null',
            $variantData['requiresShipping'] ? 'true' : 'false',
            $variantData['taxable'] ? 'true' : 'false',
            $variantData['inventoryPolicy'],
            $variantData['weight'],
            $variantData['weightUnit'],
            $inventoryQuantities ? ', ' . $inventoryQuantities : ''
        );

        $response = $this->executeGraphQLQuery($mutation);

        if (isset($response['data']['productVariantUpdate']['userErrors']) && !empty($response['data']['productVariantUpdate']['userErrors'])) {
            throw new Exception("Variant Update Errors: " . json_encode($response['data']['productVariantUpdate']['userErrors']));
        }

        return ['success' => true];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

    /**
     * Generate product handle from title
     */
    private function generateHandle($title)
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($title)));
    }

    /**
     * Check if product has variant data
     */
    private function hasVariantData($product)
    {
        return !empty($product->variant_sku) || 
               !empty($product->variant_price) || 
               !empty($product->variant_weight) ||
               !empty($product->variant_inventory_qty);
    }

    /**
     * Create new product in Shopify
     */
    private function createShopifyProduct($product)
    {
        try {
            $shopifyUrl = config('services.shopify.shop_url');
            $accessToken = config('services.shopify.shopify_api_key');

            if (!$shopifyUrl || !$accessToken) {
                throw new Exception('Shopify configuration missing');
            }

            $mutation = $this->buildProductCreateMutation($product);
            $response = $this->executeGraphQLQuery($mutation);

            if (isset($response['data']['productCreate']['userErrors']) && !empty($response['data']['productCreate']['userErrors'])) {
                throw new Exception("Product Create Errors: " . json_encode($response['data']['productCreate']['userErrors']));
            }

            if (isset($response['data']['productCreate']['product']['id'])) {
                return [
                    'success' => true,
                    'product_id' => $response['data']['productCreate']['product']['id']
                ];
            }

            throw new Exception("Unexpected response format");

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add variant to existing product - DEPRECATED
     * This method is kept for backward compatibility but should not be used
     * Use updateShopifyVariant instead
     */
    private function addVariantToProduct($productId, $product)
    {
        // Log that we're not creating variants anymore
        Log::info("Skipping variant creation - will update existing default variant instead");
        
        return [
            'success' => false,
            'error' => 'Variant creation disabled - use existing default variant update instead'
        ];
    }

    /**
     * Add product to collection
     */
    private function addProductToCollection($productId)
    {
        try {
            $shopify_collection_id=config('services.shopify.shopify_collection_id');
            $collectionId = 'gid://shopify/Collection/'.$shopify_collection_id;
            $mutation = sprintf(
                'mutation {
                    collectionAddProducts(id: "%s", productIds: ["%s"]) {
                        collection {
                            id
                        }
                        userErrors {
                            field
                            message
                        }
                    }
                }',
                $collectionId,
                $productId
            );

            $response = $this->executeGraphQLQuery($mutation);

            if (isset($response['data']['collectionAddProducts']['userErrors']) && !empty($response['data']['collectionAddProducts']['userErrors'])) {
                Log::warning("Collection add errors: " . json_encode($response['data']['collectionAddProducts']['userErrors']));
            }

        } catch (Exception $e) {
            Log::error("Failed to add product to collection: " . $e->getMessage());
        }
    }

    /**
     * Build GraphQL mutation for product creation
     */
    private function buildProductCreateMutation($product)
    {
        $tags = '';
        if (!empty($product->tags)) {
            $tagsList = array_map(function($tag) {
                return '"' . addslashes(trim($tag)) . '"';
            }, explode(',', $product->tags));
            $tags = 'tags: [' . implode(',', $tagsList) . ']';
        }

        return sprintf(
            'mutation {
                productCreate(input: {
                    title: "%s",
                    descriptionHtml: "%s",
                    vendor: "%s",
                    productType: "%s",
                    published: %s
                    %s
                }) {
                    product {
                        id
                        title
                        handle
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }',
            addslashes($product->title),
            addslashes($product->body_html ?: ''),
            addslashes($product->vendor ?: ''),
            addslashes($product->product_type ?: ''),
            $product->published === 'true' ? 'true' : 'false',
            $tags ? ', ' . $tags : ''
        );
    }

    /**
     * Prepare variant data
     */
    private function prepareVariantData($product)
    {
        return [
            'sku' => $product->variant_sku ?: '',
            'price' => $product->variant_price ?: '0.00',
            'compareAtPrice' => $product->variant_compare_at_price ?: null,
            'requiresShipping' => $product->variant_requires_shipping === 'true',
            'taxable' => $product->variant_taxable === 'true',
            'inventoryPolicy' => strtoupper($product->variant_inventory_policy ?: 'DENY'),
            'weight' => (float)($product->variant_weight ?: 0),
            'weightUnit' => $this->mapWeightUnit($product->variant_weight_unit ?: 'kg')
        ];
    }

    /**
     * Build GraphQL mutation for variant creation - DEPRECATED
     * This method is no longer used as we update the default variant instead
     */
    private function buildVariantCreateMutation($productId, $variantData)
    {
        Log::info("buildVariantCreateMutation is deprecated - using default variant update instead");
        return null;
    }

    /**
     * Get the primary location ID for inventory management
     */
    private function getPrimaryLocationId()
    {
        try {
            $query = 'query {
                locations(first: 1) {
                    edges {
                        node {
                            id
                            name
                            isPrimary
                        }
                    }
                }
            }';

            $response = $this->executeGraphQLQuery($query);
            
            if (isset($response['data']['locations']['edges'][0]['node']['id'])) {
                return $response['data']['locations']['edges'][0]['node']['id'];
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error("Error getting location ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Execute GraphQL query with cURL
     */
    private function executeGraphQLQuery($mutation)
    {
        $shopifyUrl = config('services.shopify.shop_url');
        $accessToken = config('services.shopify.shopify_api_key');

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $shopifyUrl . '/admin/api/2023-10/graphql.json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(['query' => $mutation]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Shopify-Access-Token: ' . $accessToken
            ],
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode);
        }

        $result = json_decode($response, true);

        if (isset($result['errors'])) {
            throw new Exception("GraphQL Errors: " . json_encode($result['errors']));
        }

        return $result;
    }

    /**
     * Map weight unit to Shopify accepted values
     */
    private function mapWeightUnit($unit)
    {
        $unitMap = [
            'kg' => 'KILOGRAMS',
            'kilograms' => 'KILOGRAMS',
            'g' => 'GRAMS',
            'grams' => 'GRAMS',
            'lb' => 'POUNDS',
            'pounds' => 'POUNDS',
            'oz' => 'OUNCES',
            'ounces' => 'OUNCES'
        ];
        
        return $unitMap[strtolower($unit)] ?? 'KILOGRAMS';
    }

    /**
     * Add or update product image
     */
    private function addOrUpdateProductImage($productId, $imageSrc, $altText = null)
    {
        try {
            if (empty($imageSrc)) {
                return;
            }

            $mutation = sprintf(
                'mutation {
                    productCreateMedia(productId: "%s", media: [{
                        originalSource: "%s",
                        mediaContentType: IMAGE,
                        alt: "%s"
                    }]) {
                        media {
                            id
                        }
                        mediaUserErrors {
                            field
                            message
                        }
                    }
                }',
                $productId,
                addslashes($imageSrc),
                addslashes($altText ?: '')
            );

            $this->executeGraphQLQuery($mutation);
            Log::info("Image processed for product: {$productId}");

        } catch (Exception $e) {
            Log::error("Failed to process product image: " . $e->getMessage());
        }
    }

    /**
     * Update upload progress
     */
    private function updateUploadProgress()
    {
        $stats = DB::table('products')
            ->where('upload_id', $this->uploadId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN import_status = "success" THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN import_status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN import_status != "pending" THEN 1 ELSE 0 END) as processed
            ')
            ->first();

        DB::table('uploads')
            ->where('id', $this->uploadId)
            ->update([
                'processed_rows' => $stats->processed,
                'successful_rows' => $stats->successful,
                'failed_rows' => $stats->failed,
                'updated_at' => now()
            ]);
    }
}