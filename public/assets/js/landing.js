(function () {
  const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
  const navbarCollapse = document.querySelector('.navbar-collapse');
  if (!navLinks.length || !navbarCollapse) return;

  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth < 992 && window.bootstrap && window.bootstrap.Collapse) {
        const bsCollapse = new window.bootstrap.Collapse(navbarCollapse, { toggle: false });
        bsCollapse.hide();
      }
    });
  });
})();
