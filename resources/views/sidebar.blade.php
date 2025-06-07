<div class="sidebar">
        <nav class="sidebar-nav">
            <div class="nav flex-column">
                <a href="#" class="nav-link">
                    <p class="menu-text">Menu</p>
                </a>
                <a href="#" class="nav-link active mb-1">
                    <i class="bi bi-speedometer2 fs-4 me-2"></i>  Dashboard
                </a>
                <a href="{{route('import-list-get')}}" class="nav-link projects-toggle mb-1" id="listofimport">
                    <i class="bi bi-list-ul fs-4 me-2"></i> Import List
                </a>
            </div>
        </nav>
        <div class="sidebar-nav mt-auto">
            <div class="nav flex-column">
                <a href="#" class="nav-link">
                    <p class="setting-text">Settings</p>
                </a>
                <a href="#" class="nav-link mb-1">
                    <i class="bi bi-gear fs-4 me-2"></i> Settings
                </a>
                <a href="{{route('logout-user')}}" class="nav-link mb-1">
                     <i class="bi bi-box-arrow-right fs-4 me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>