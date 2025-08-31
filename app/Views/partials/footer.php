  <div id="overlay" onclick="toggleSidebar()"></div>
  <style>
    #overlay {
      position: fixed;
      top:0; left:0;
      width:100%; height:100%;
      background:rgba(0,0,0,0.4);
      z-index:1040;
      display:none;
    }
    #overlay.show { display:block; }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function toggleSidebar(){
      document.getElementById('sidebar').classList.toggle('show');
      document.getElementById('overlay').classList.toggle('show');
    }
  </script>
</body>
</html>
