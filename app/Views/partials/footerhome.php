<footer class="footer">
  <div class="container">
    <div class="row">
      <div class="col-lg-4 mb-4">
        <h5 class="fw-bold mb-3">SNIA <?= isset($activeEvent['event_date']) ? date('Y', strtotime($activeEvent['event_date'])) : date('Y') ?></h5>
        <p>Seminar Nasional Informatika & Aplikasi oleh Jurusan Informatika UNJANI Cimahi.</p>
      </div>

      <div class="col-lg-4 mb-4">
        <h5 class="fw-bold mb-3">Penyelenggara</h5>
        <p class="mb-2"><i class="fas fa-university me-2"></i>Jurusan Informatika UNJANI</p>
        <p class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>Universitas Jenderal Achmad Yani, Cimahi</p>
        <p class="mb-0"><i class="fas fa-gift me-2"></i>Dua tahunan</p>
      </div>

      <div class="col-lg-4 mb-4">
        <h5 class="fw-bold mb-3">Kontak</h5>
        <p class="mb-2"><i class="fas fa-envelope me-2"></i><a href="mailto:snia@unjani.ac.id">snia@unjani.ac.id</a></p>
        <p class="mb-2"><i class="fas fa-phone me-2"></i>+62 22 6656 186</p>
        <p class="mb-3"><i class="fas fa-globe me-2"></i><a href="https://www.unjani.ac.id" target="_blank" rel="noopener">unjani.ac.id</a></p>

        <!-- CTA sponsor dipindahkan ke sini -->
        <p class="mb-0"><i class="fas fa-handshake me-2"></i>
          Tertarik menjadi sponsor? Hubungi kami di
          <a href="mailto:sponsor@snia.unjani.ac.id">sponsor@snia.unjani.ac.id</a>
        </p>
      </div>
    </div>

    <hr class="my-4" style="border-color: rgba(255,255,255,0.25);">
    <div class="text-center">
      <p class="mb-0">&copy; <?= date('Y') ?> SNIA - Jurusan Informatika UNJANI</p>
    </div>
  </div>
</footer>
