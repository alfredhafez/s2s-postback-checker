<nav class="navbar">
    <div class="d-flex justify-between align-center w-full">
        <a href="index.php" class="navbar-brand">S2S Postback Checker</a>
        
        <ul class="navbar-nav">
            <li><a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="offers.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'offers.php' ? 'active' : '' ?>">Offers</a></li>
            <li><a href="postback-test.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'postback-test.php' ? 'active' : '' ?>">Postback Test</a></li>
            <li><a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">Settings</a></li>
        </ul>
    </div>
</nav>