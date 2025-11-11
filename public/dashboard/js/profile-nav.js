// JS extracted from clinic-listing.php to reduce file clutter.
// Provides the goToProfile function used by the Profile Settings dropdown.
function goToProfile(event) {
    if (event && typeof event.preventDefault === 'function') event.preventDefault();

    // Attempt to show a small loading state on the clicked link
    const profileLink = event && event.target ? event.target.closest('.dropdown-item') : null;
    const originalContent = profileLink ? profileLink.innerHTML : '';
    if (profileLink) {
        profileLink.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading Profile...';
    }

    // Navigate to the dedicated profile page
    setTimeout(() => {
        window.location.href = 'profile.php';
    }, 500);
}
