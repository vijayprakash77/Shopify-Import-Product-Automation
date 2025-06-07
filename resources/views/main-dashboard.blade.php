<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopify CMS - Dashboard</title>
    @include('header')
    
</head>
<body>
     <!-- Sidebar Logo -->
    <div class="sidebar-logo">

     <h4>Shopify CMS</h4>
    </div>

    <!-- Sidebar -->
	 @include('sidebar')
    <!-- end sidebar -->

    <!-- Topbar -->
	 @include('topbar')
     <!-- end topbar -->
	  @include('auth.session-message')

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-content mt-4">
			
          @if(Route::currentRouteName() == 'new-import-get')
				@yield('new-import')

			@elseif(Route::currentRouteName() == 'import-list-get')
				    @yield('import-list')
            @else
		 @endif
          

               
        </div>
    </div>
	<!-- end main content -->

   @include('footer')

</body>
</html>