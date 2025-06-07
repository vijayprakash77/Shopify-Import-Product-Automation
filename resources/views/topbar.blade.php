<div class="topbar">
        <div class="topbar-actions">
            <a href="{{route('new-import-get')}}" class="nav-link">
            <button class="btn btn-primary"><i class="bi bi-arrow-repeat"></i>
                 Import
            </button>
          </a>
            <div class="position-relative notification-bel">
                <img src="images/bel.svg" alt="notifications" class="rounded-circle me-2" width="25" height="25">
                <span class="badge bg-primary rounded-circle notification-badge">2</span>
            </div>
            <div class="dropdown top-avatar-dropdown">
                <a href="#" class="dropdown-toggle text-decoration-none" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <span>{{Auth::user()->name}}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                <li>
                    <a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('import-list-get') }}"><i class="bi bi-list-ul me-2"></i>Import List</a>
                </li>
               
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('logout-user') }}">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </li>
            </ul>
            </div>
        </div>
    </div>