/* Absensi SDN Tulungrejo 01 - front-end helpers */

/** Konfirmasi hapus data */
document.addEventListener('submit', function (e) {
  var form = e.target;
  if (form.classList.contains('confirm-submit')) {
    if (!confirm(form.dataset.confirm || 'Yakin ingin menghapus data ini?')) {
      e.preventDefault();
    }
  }
});

/** Pilih semua status "Hadir" secara otomatis saat form absensi dibuka */
function setAllStatus(val) {
  document.querySelectorAll('select[name="status[]"]').forEach(function (s) {
    s.value = val;
  });
}

/** Cetak halaman */
function cetak() {
  window.print();
}

/** Toggle sidebar di layar kecil (legacy fallback) */
function toggleSidebar() {
  var sb = document.getElementById('sidebar');
  if (sb) sb.classList.toggle('open');
}
