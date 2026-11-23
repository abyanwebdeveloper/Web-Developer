<?php
/* Perilaku halaman admin: grafik dasbor, pencarian AJAX produk, konfirmasi hapus. */
(() => {
  // Grafik dasbor: muat deret harian lalu render batang.
  const chart = document.querySelector('[data-chart]');
  if (chart) {
    fetch('data/statistik.php?hari=7', {credentials: 'same-origin'})
      .then(r => r.json())
      .then(res => {
        if (!res.ok) return;
        const max = Math.max(1, ...res.data.map(d => d.omzet));
        const today = new Date().toISOString().slice(0, 10);
        chart.innerHTML = res.data.map(d => {
          const pct = Math.max(4, Math.round(d.omzet / max * 100));
          const current = d.tanggal === today ? ' current' : '';
          const label = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][new Date(d.tanggal + 'T00:00:00').getDay()];
          return `<div class="bar-col" title="${d.label} · ${res.data.length ? 'Rp ' + Number(d.omzet).toLocaleString('id-ID') : ''} transaksi ${d.transaksi}">
                    <div class="bar-track"><div class="bar-fill${current}" style="height:${pct}%"></div></div>
                    <span>${label}</span>
                  </div>`;
        }).join('');
      })
      .catch(() => {});
  }

  // Pencarian AJAX produk (bila dipakai di halaman kelola produk).
  const box = document.querySelector('#ajaxProductSearch');
  if (box) {
    const tbody = document.querySelector('#ajaxProductRows');
    const empty = document.querySelector('#ajaxProductEmpty');
    let timer;
    const run = () => {
      const q = box.value.trim();
      fetch('data/produk.php?q=' + encodeURIComponent(q), {credentials: 'same-origin'})
        .then(r => r.json())
        .then(res => {
          if (!res.ok) return;
          tbody.innerHTML = res.data.map(p => `
            <tr>
              <td><span class="avatar-chip">${p.emoji || '📦'}</span></td>
              <td><strong>${p.name}</strong><br><small class="text-secondary">${p.sku}</small></td>
              <td>${p.category}</td>
              <td>Rp ${Number(p.price).toLocaleString('id-ID')}</td>
              <td><span class="pill ${p.stock <= p.min_stock ? 'warn' : ''}">${p.stock} stok</span></td>
            </tr>`).join('');
          empty.classList.toggle('d-none', res.data.length > 0);
        })
        .catch(() => {});
    };
    box.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(run, 280); });
  }

  // Konfirmasi hapus.
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('submit', e => {
      if (!window.confirm(el.dataset.confirm)) e.preventDefault();
    });
  });
})();
