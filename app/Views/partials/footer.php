  <!-- Overlay gelap FADE dengan delay, di bawah sidebar -->
  <div id="overlay" onclick="toggleSidebar()" aria-hidden="true"></div>

  <style>
    #overlay{
      position:fixed; inset:0;
      background:rgba(0,0,0,.4);
      z-index:1040;                     /* < sidebar(1050) */
      opacity:0; visibility:hidden; pointer-events:none;
      transition: opacity .28s ease;
    }
    /* aktif tapi masih transparan (agar bisa di-fade in/out) */
    #overlay.visible{ visibility:visible; pointer-events:auto; }
    /* tampilan penuh */
    #overlay.show{ opacity:1; }
    /* saat buka, tambahkan sedikit delay agar sidebar mulai slide dulu */
    #overlay.delayed{ transition-delay:.12s; }

    /* Desktop: overlay tidak dipakai karena sidebar statis */
    @media (min-width: 992px){
      #overlay{ display:none; }
    }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function toggleSidebar(){
      const sb = document.getElementById('sidebar');
      const ov = document.getElementById('overlay');
      const willOpen = !sb.classList.contains('show');

      if (willOpen){
        // buka: tandai visible dulu, kasih delay untuk sinkron,
        ov.classList.add('visible','delayed');
        // force reflow supaya transition jalan
        void ov.offsetWidth;
        ov.classList.add('show');

        sb.classList.add('show');
        document.body.classList.add('sb-open');
      } else {
        // tutup: hilangkan delay agar fade-out langsung
        ov.classList.remove('delayed');
        ov.classList.remove('show');
        // setelah fade-out selesai sembunyikan sepenuhnya
        const onEnd = (e)=>{
          if (e.propertyName === 'opacity'){
            ov.classList.remove('visible');
            ov.removeEventListener('transitionend', onEnd);
          }
        };
        ov.addEventListener('transitionend', onEnd);

        sb.classList.remove('show');
        document.body.classList.remove('sb-open');
      }
    }

    // Jika resize ke desktop, bersihkan state
    window.addEventListener('resize', ()=>{
      if (window.matchMedia('(min-width: 992px)').matches){
        const ov = document.getElementById('overlay');
        const sb = document.getElementById('sidebar');
        ov.classList.remove('show','delayed','visible');
        sb.classList.remove('show');
        document.body.classList.remove('sb-open');
      }
    });
  </script>
</body>
</html>
