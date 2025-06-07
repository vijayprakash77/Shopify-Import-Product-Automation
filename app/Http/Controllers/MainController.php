<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Hash;
use Session;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use DB; 
use Mail; 
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cookie;
use DateTime;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;
use App\Jobs\ProcessShopifyImport;
use Illuminate\Support\Facades\Validator;

class MainController extends Controller
{
    //
     public function newImportGet()
    {
        if (!Auth::check()) {
            return redirect("/login")->with(['error' => 'You are not allowed to access this page']);
        }

        return view('new-import');
    }

    // Process CSV file upload
    public function processImport(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('imports', $filename);
           //dd($filePath);
            // Read and validate CSV
            //$csvData = $this->readCsvFile(storage_path('app/' . $filePath));
            //dd($csvData);
            $fullPath = Storage::path($filePath);
            $csvData = $this->readCsvFile($fullPath);
            if (!$csvData['success']) {
                // Delete uploaded file if invalid
                Storage::delete($filePath);
                return response()->json([
                    'success' => false,
                    'message' => $csvData['message']
                ], 422);
            }

            // Create upload record
            $uploadId = DB::table('uploads')->insertGetId([
                'filename' => $filename,
                'file_path' => $filePath,
                'total_rows' => $csvData['total_rows'],
                'processed_rows' => 0,
                'successful_rows' => 0,
                'failed_rows' => 0,
                'status' => 'processing',
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Insert products data
            $this->insertProductsData($csvData['data'], $uploadId);

            // Dispatch job to process Shopify import
            ProcessShopifyImport::dispatch($uploadId);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully and processing started',
                'upload_id' => $uploadId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get import progress
    public function getImportProgress($uploadId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $upload = DB::table('uploads')->where('id', $uploadId)->first();

            if (!$upload) {
                return response()->json(['error' => 'Upload not found'], 404);
            }

            return response()->json([
                'status' => $upload->status,
                'total_rows' => (int)$upload->total_rows,
                'processed_rows' => (int)$upload->processed_rows,
                'successful_rows' => (int)$upload->successful_rows,
                'failed_rows' => (int)$upload->failed_rows,
                'started_at' => $upload->started_at,
                'completed_at' => $upload->completed_at,
                'errors' => $upload->errors
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching progress'], 500);
        }
    }

    // Pause import
    public function pauseImport($uploadId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            DB::table('uploads')
                ->where('id', $uploadId)
                ->update([
                    'status' => 'paused',
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Import paused successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error pausing import: ' . $e->getMessage()
            ], 500);
        }
    }

    // Resume import
    public function resumeImport($uploadId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            DB::table('uploads')
                ->where('id', $uploadId)
                ->update([
                    'status' => 'processing',
                    'updated_at' => now()
                ]);

            // Dispatch job again to continue processing
            ProcessShopifyImport::dispatch($uploadId);

            return response()->json([
                'success' => true,
                'message' => 'Import resumed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error resuming import: ' . $e->getMessage()
            ], 500);
        }
    }

    // Download sample CSV
    public function downloadSampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sample_products.csv"',
        ];

        $sampleData = [
            ['Handle', 'Title', 'Body HTML', 'Vendor', 'Product type', 'Tags', 'Published', 'Variant SKU', 'Variant Price', 'Variant Compare At Price', 'Variant Requires Shipping', 'Variant Taxable', 'Variant Inventory Tracker', 'Variant Inventory Qty', 'Variant Inventory Policy', 'Variant Fulfillment Service', 'Variant Weight', 'Variant Weight Unit', 'Image Src', 'Image Position', 'Image Alt Text'],
            ['sample-product-1', 'Sample Product 1', '<p>This is a sample product description</p>', 'Sample Vendor', 'Electronics', 'sample,product,electronics', 'true', 'SKU001', '29.99', '39.99', 'true', 'true', 'shopify', '100', 'deny', 'manual', '0.5', 'kg', 'https://example.com/image1.jpg', '1', 'Sample product image'],
            ['sample-product-2', 'Sample Product 2', '<p>Another sample product description</p>', 'Sample Vendor', 'Fashion', 'sample,product,fashion', 'true', 'SKU002', '49.99', '59.99', 'true', 'true', 'shopify', '50', 'deny', 'manual', '0.3', 'kg', 'https://example.com/image2.jpg', '1', 'Another sample product image']
        ];

        $callback = function() use ($sampleData) {
            $file = fopen('php://output', 'w');
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Read CSV file and validate
    private function readCsvFile($filePath)
    {
        try {
            if (!file_exists($filePath)) {
                return ['success' => false, 'message' => 'File not found'];
            }

            $handle = fopen($filePath, 'r');
            if (!$handle) {
                return ['success' => false, 'message' => 'Unable to read file'];
            }

            // Read header row
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return ['success' => false, 'message' => 'Invalid CSV format'];
            }

            // Clean headers
            $headers = array_map('trim', $headers);
            //$headers = array_map('strtolower', $headers);

            // Required columns
            $requiredColumns = ['Handle', 'Title', 'Body HTML', 'Vendor', 'Product Type'];
            $missingColumns = array_diff($requiredColumns, $headers);
            
            if (!empty($missingColumns)) {
                fclose($handle);
                return [
                    'success' => false, 
                    'message' => 'Missing required columns: ' . implode(', ', $missingColumns)
                ];
            }

            // Read data rows
            $data = [];
            $rowCount = 0;
            
            while (($row = fgetcsv($handle)) !== false) {
                if (empty(array_filter($row))) continue; // Skip empty rows
                
                $rowData = array_combine($headers, $row);
                $data[] = $rowData;
                $rowCount++;
                
                // Limit to prevent memory issues
                if ($rowCount > 10000) {
                    fclose($handle);
                    return [
                        'success' => false, 
                        'message' => 'File too large. Maximum 10,000 rows allowed.'
                    ];
                }
            }

            fclose($handle);

            if (empty($data)) {
                return ['success' => false, 'message' => 'No data found in CSV'];
            }

            return [
                'success' => true,
                'data' => $data,
                'total_rows' => count($data),
                'headers' => $headers
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error reading CSV: ' . $e->getMessage()];
        }
    }

    // Insert products data into database
    private function insertProductsData($data, $uploadId)
    {
        foreach ($data as $row) {
            // Check if product already exists
            $existingProduct = DB::table('products')
                ->where('upload_id', $uploadId)
                ->where('handle', $row['handle'] ?? '')
                ->first();

            $productData = [
                'upload_id' => $uploadId,
                'handle' => $row['Handle'] ?? '',
                'title' => $row['Title'] ?? '',
                'body_html' => $row['Body HTML'] ?? $row['body (html)'] ?? '',
                'vendor' => $row['vendor'] ?? '',
                'product_type' => $row['Product Type'] ?? $row['type'] ?? '',
                'tags' => $row['Tags'] ?? '',
                'published' => $row['Published'] ?? 'true',
                'variant_sku' => $row['Variant SKU'] ?? $row['variant sku'] ?? '',
                'variant_price' => $row['Variant Price'] ?? $row['variant price'] ?? '',
                'variant_compare_at_price' => $row['Variant Compare At Price'] ?? $row['variant compare at price'] ?? '',
                'variant_requires_shipping' => $row['Variant Requires Shipping'] ?? 'true',
                'variant_taxable' => $row['Variant Taxable'] ?? 'true',
                'variant_inventory_tracker' => $row['Variant Inventory Tracker'] ?? 'shopify',
                'variant_inventory_qty' => $row['Variant Inventory Qty'] ?? $row['variant inventory qty'] ?? '0',
                'variant_inventory_policy' => $row['Variant Inventory Policy'] ?? 'deny',
                'variant_fulfillment_service' => $row['Variant Fulfillment Service'] ?? 'manual',
                'variant_weight' => $row['Variant Weight'] ?? $row['variant weight'] ?? '',
                'variant_weight_unit' => $row['Variant Weight Unit'] ?? $row['variant weight unit'] ?? 'kg',
                'image_src' => $row['Image Src'] ?? $row['image src'] ?? '',
                'image_position' => $row['Image Position'] ?? $row['image position'] ?? '1',
                'image_alt_text' => $row['Image Alt Text'] ?? $row['image alt text'] ?? '',
                'import_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ];

            if ($existingProduct) {
                // Update existing product
                DB::table('products')
                    ->where('id', $existingProduct->id)
                    ->update($productData);
            } else {
                // Insert new product
                DB::table('products')->insert($productData);
            }
        }
    }
    public function new_import_get(){

                if(!Auth::check()){

                   return redirect("/login")->with(['success','You are not allowed to access this page']);
                }

                return view('new-import');
     }

    public function import_list_get(){
        if(!Auth::check()){
            return redirect("/login")->with(['success','You are not allowed to access this page']);
        }

        return view('import-list');
    }

    // Yajra DataTables AJAX endpoint
    public function import_list_ajax(Request $request)
    {
       // dd($request);
        if(!Auth::check()){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = DB::table('uploads')
            ->select([
                'id',
                'filename',
                'file_path',
                'total_rows',
                'processed_rows',
                'successful_rows',
                'failed_rows',
                'status',
                'errors',
                'started_at',
                'completed_at',
                'created_at'
            ]);

        return DataTables::of($query)
            ->addColumn('file_info', function ($upload) {
                $extension = pathinfo($upload->filename, PATHINFO_EXTENSION);
                $icon = '';
                
                switch(strtolower($extension)) {
                    case 'csv':
                        $icon = '<i class="bi bi-filetype-csv text-primary"></i>';
                        break;
                    case 'xlsx':
                    case 'xls':
                        $icon = '<i class="bi bi-filetype-xlsx text-success"></i>';
                        break;
                    default:
                        $icon = '<i class="bi bi-file-earmark-text text-primary"></i>';
                }

                $filename = $upload->filename ?? 'Unknown File';
                $totalRows = $upload->total_rows ? number_format($upload->total_rows) . ' rows' : '';
                $processedRows = $upload->processed_rows ? 'Processed: ' . number_format($upload->processed_rows) : '';
                
                $subtitle = [];
                if($totalRows) $subtitle[] = 'Total: ' . $totalRows;
                if($processedRows) $subtitle[] = $processedRows;
                $subtitleText = implode(' | ', $subtitle);

                return '
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                        ' . $icon . '
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold">' . $filename . '</h6>
                        ' . ($subtitleText ? '<small class="text-muted">' . $subtitleText . '</small>' : '') . '
                    </div>
                </div>';
            })
            ->addColumn('status_badge', function ($upload) {
                $status = $upload->status ?? 'unknown';
                
                switch(strtolower($status)) {
                    case 'completed':
                        return '<span class="badge bg-success bg-opacity-15  border-opacity-25 px-3 py-2">
                                    <i class="bi bi-check-circle me-1"></i>Completed
                                </span>';
                    case 'processing':
                        return '<span class="badge bg-warning bg-opacity-15 border-opacity-25 px-3 py-2">
                                    <i class="bi bi-hourglass-split me-1"></i>Processing
                                </span>';
                    case 'failed':
                        return '<span class="badge bg-danger bg-opacity-15  border-opacity-25 px-3 py-2">
                                    <i class="bi bi-x-circle me-1"></i>Failed
                                </span>';
                    case 'pending':
                        return '<span class="badge bg-info bg-opacity-15 border-opacity-25 px-3 py-2">
                                    <i class="bi bi-clock me-1"></i>Pending
                                </span>';
                    default:
                        return '<span class="badge bg-secondary bg-opacity-15 text-secondary border border-secondary border-opacity-25 px-3 py-2">
                                    <i class="bi bi-question-circle me-1"></i>' . ucfirst($status) . '
                                </span>';
                }
            })
            ->addColumn('started_formatted', function ($upload) {
                if ($upload->started_at) {
                    $date = Carbon::parse($upload->started_at);
                    return '<div class="text-dark fw-medium">' . $date->format('M d, Y') . '</div>
                            <small class="text-muted">' . $date->format('H:i A') . '</small>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('completed_formatted', function ($upload) {
                if ($upload->completed_at) {
                    $date = Carbon::parse($upload->completed_at);
                    return '<div class="text-dark fw-medium">' . $date->format('M d, Y') . '</div>
                            <small class="text-muted">' . $date->format('H:i A') . '</small>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('actions', function ($upload) {
                $importAgainBtn = '';
                if (in_array(strtolower($upload->status), ['failed', 'completed'])) {
                    $importAgainBtn = '
                    <li>
                        <a class="dropdown-item" href="#" onclick="importAgain(' . $upload->id . ')">
                            <i class="bi bi-arrow-repeat me-2 text-success"></i>Import Again
                        </a>
                    </li>';
                }

                return '
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle border-0" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                        <li>
                            <a class="dropdown-item" href="#" onclick="viewDetails(' . $upload->id . ')">
                                <i class="bi bi-eye me-2 text-primary"></i>View Details
                            </a>
                        </li>
                        ' . $importAgainBtn . '
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="deleteImport(' . $upload->id . ')">
                                <i class="bi bi-trash me-2"></i>Delete
                            </a>
                        </li>
                    </ul>
                </div>';
            })
            ->filter(function ($query) use ($request) {
                // Status filter
                if ($request->has('status_filter') && $request->status_filter !== 'select_status') {
                    $query->where('status', $request->status_filter);
                }

                // Date filter
                if ($request->has('date_filter') && !empty($request->date_filter)) {
                    $query->whereDate('started_at', $request->date_filter);
                }

                // Custom search in filename
                if ($request->has('search_term') && !empty($request->search_term)) {
                    $searchTerm = $request->search_term;
                    $query->where(function($q) use ($searchTerm) {
                        $q->where('filename', 'like', "%{$searchTerm}%")
                          ->orWhere('status', 'like', "%{$searchTerm}%");
                    });
                }
            })
            ->rawColumns(['file_info', 'status_badge', 'started_formatted', 'completed_formatted', 'actions'])
            ->orderColumn('started_formatted', 'started_at $1')
            ->orderColumn('completed_formatted', 'completed_at $1')
            ->orderColumn('file_info', 'filename $1')
            ->orderColumn('status_badge', 'status $1')
            ->make(true);
    }

    // Method to get import details for modal
    public function getImportDetails($id) {
        if(!Auth::check()){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $upload = DB::table('uploads')->where('id', $id)->first();
        $products = DB::table('products')
                        ->where('upload_id', $id)
                        ->select('title', 'import_status')
                        ->get();
        if (!$upload) {
            return response()->json(['error' => 'Import not found'], 404);
        }

        // Calculate progress percentage
        $progressPercentage = 0;
        if ($upload->total_rows && $upload->total_rows > 0) {
            $progressPercentage = round(($upload->processed_rows / $upload->total_rows) * 100, 2);
        }

        return response()->json([
            'id' => $upload->id,
            'filename' => $upload->filename,
            'products' => $products,
            'file_path' => $upload->file_path,
            'total_rows' => number_format($upload->total_rows ?? 0),
            'processed_rows' => number_format($upload->processed_rows ?? 0),
            'successful_rows' => number_format($upload->successful_rows ?? 0),
            'failed_rows' => number_format($upload->failed_rows ?? 0),
            'progress_percentage' => $progressPercentage,
            'status' => $upload->status,
            'errors' => $upload->errors,
            'started_at' => $upload->started_at ? Carbon::parse($upload->started_at)->format('M d, Y H:i A') : null,
            'completed_at' => $upload->completed_at ? Carbon::parse($upload->completed_at)->format('M d, Y H:i A') : null,
            'created_at' => Carbon::parse($upload->created_at)->format('M d, Y H:i A'),
            'duration' => $this->calculateDuration($upload->started_at, $upload->completed_at)
        ]);
    }

    // Method to restart import
    public function importAgain($id) {
        if(!Auth::check()){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $upload = DB::table('uploads')->where('id', $id)->first();
        
        if (!$upload) {
            return response()->json(['error' => 'Import not found'], 404);
        }

        // Check if file still exists
        if (!$upload->file_path) {
            return response()->json(['error' => 'Original file not found'], 404);
        }

        // Reset the import status and timestamps
        DB::table('uploads')->where('id', $id)->update([
            'status' => 'pending',
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'errors' => null,
            'started_at' => null,
            'completed_at' => null,
            'updated_at' => now()
        ]);

        // Here you would typically dispatch your import job again
        // Example: ImportJob::dispatch($upload);
        ProcessShopifyImport::dispatch($id);
        return response()->json([
            'success' => true,
            'message' => 'Import restarted successfully'
        ]);
    }

    // Method to delete import
    public function deleteImport($id) {
        if(!Auth::check()){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $upload = DB::table('uploads')->where('id', $id)->first();
        
        if (!$upload) {
            return response()->json(['error' => 'Import not found'], 404);
        }

        // Don't delete if currently processing
        if (strtolower($upload->status) === 'processing') {
            return response()->json(['error' => 'Cannot delete import that is currently processing'], 400);
        }

        // Delete the file if it exists
        if ($upload->file_path && file_exists(storage_path('app/' . $upload->file_path))) {
            unlink(storage_path('app/' . $upload->file_path));
        }

        // Delete the database record
        DB::table('uploads')->where('id', $id)->delete();
        DB::table('products')->where('upload_id', $id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Import deleted successfully'
        ]);
    }

    // Helper method to calculate duration
    private function calculateDuration($startedAt, $completedAt) {
        if (!$startedAt || !$completedAt) {
            return null;
        }

        $start = Carbon::parse($startedAt);
        $end = Carbon::parse($completedAt);
        
        return $start->diffForHumans($end, true);
    }

    // Method to get import statistics
    public function getImportStats() {
        if(!Auth::check()){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $stats = [
            'total_imports' => DB::table('uploads')->count(),
            'completed_imports' => DB::table('uploads')->where('status', 'completed')->count(),
            'processing_imports' => DB::table('uploads')->where('status', 'processing')->count(),
            'failed_imports' => DB::table('uploads')->where('status', 'failed')->count(),
            'pending_imports' => DB::table('uploads')->where('status', 'pending')->count(),
            'total_rows_processed' => DB::table('uploads')->sum('processed_rows') ?? 0,
            'total_successful_rows' => DB::table('uploads')->sum('successful_rows') ?? 0,
            'total_failed_rows' => DB::table('uploads')->sum('failed_rows') ?? 0,
            'success_rate' => $this->calculateSuccessRate()
        ];

        return response()->json($stats);
    }

    // Helper method to calculate success rate
    private function calculateSuccessRate() {
        $totalProcessed = DB::table('uploads')->sum('processed_rows') ?? 0;
        $totalSuccessful = DB::table('uploads')->sum('successful_rows') ?? 0;
        
        if ($totalProcessed > 0) {
            return round(($totalSuccessful / $totalProcessed) * 100, 2);
        }
        
        return 0;
    }

    public function admin_dashboard_get(){

                if(!Auth::check()){

                   return redirect("/login")->with(['success','You are not allowed to access this page']);
                }

                return view('main-dashboard');
     }

    public function signin_get(){

                    return view('auth.login');
    }

    public function logged_in_user(Request $request){

                    try {
                        if (empty($request->input('email'))) {
                            return redirect()->back()->with('error', 'Email is required.');
                        }

                        if (empty($request->input('password'))) {
                            return redirect()->back()->with('error', 'Password is required.');
                        }

                        $user = DB::table('users')->where('email', $request->input('email'))->first();

                        if (!$user) {
                            return redirect()->back()->with('error', 'User does not exist.');
                        }

                        if (is_null($user->email_verified_at)) {
                            return redirect()->back()->with('error', 'Your email is not verified. Please verify your email first.');
                        }

                        if (!Hash::check($request->input('password'), $user->password)) {
                            return redirect()->back()->with('error', 'Incorrect password.');
                        }

                        if (Auth::attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
                            return redirect('/dashboard')->with('success', 'Logged in successfully!');
                        }

                        return redirect()->back()->with('error', 'Login failed. Please try again.');

                    } catch (\Exception $e) {
                        //Log::error('Error during logged in user:- ' . $e->getMessage());
                          return redirect()->back()->with('error', 'Something went wrong.when user try to logged in:-'.$e->getMessage());
                    }
    }

     public function logout_user() {

                    try {
                        Session::flush();
                        Auth::logout();

                        return redirect('/login')->with('success', 'You have logged out successfully.');
                    } catch (\Exception $e) {
                        return redirect()->back()->with('error', 'Something went wrong while logging out. Please try again later.:-'.$e->getMessage());
                    }
     }
   
}
