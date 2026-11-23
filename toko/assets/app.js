(() => {
  const page = document.body.dataset.page;
  const rupiah = value => 'Rp ' + new Intl.NumberFormat('id-ID', {maximumFractionDigits: 0}).format(value);
  // Ubah path foto (assets/produk/x.jpg) menjadi URL lengkap sesuai lokasi aplikasi.
  const fotoSrc = value => {
    let v = String(value || '').trim();
    if (!v) return '';
    if (!/^(https?:)?\/\//i.test(v) && v.charAt(0) !== '/') {
      const meta = document.querySelector('meta[name="app-base"]');
      v = (meta ? meta.content : '') + '/' + v.replace(/^\.?\//, '');
    }
    return v;
  };
  // Tag <img> untuk foto produk di keranjang — kosong bila tidak ada foto.
  const fotoTag = product => {
    const src = fotoSrc(product.image_url || product.image);
    return src ? `<img class="foto-produk" src="${escapeHtml(src)}" alt="" loading="lazy" onerror="this.remove()">` : '';
  };

  if (page === 'products') {
    const dialog = document.querySelector('#productDialog');
    const form = document.querySelector('#productForm');
    const rows = [...document.querySelectorAll('#productRows tr')];
    const search = document.querySelector('#productSearch');
    const count = document.querySelector('#visibleCount');
    const empty = document.querySelector('#productEmpty');
    let category = 'all';

    function filterProducts() {
      const term = search.value.trim().toLocaleLowerCase('id-ID');
      let visible = 0;
      rows.forEach(row => {
        const show = (category === 'all' || row.dataset.category === category) && row.dataset.search.includes(term);
        row.hidden = !show;
        if (show) visible++;
      });
      count.textContent = visible;
      empty.hidden = visible !== 0;
    }
    search.addEventListener('input', filterProducts);
    document.querySelectorAll('#productCategories .category-tab').forEach(button => {
      button.addEventListener('click', () => {
        document.querySelector('#productCategories .active')?.classList.remove('active');
        button.classList.add('active');
        category = button.dataset.category;
        filterProducts();
      });
    });
    document.querySelector('#addProduct').addEventListener('click', () => {
      form.reset();
      form.elements.id.value = '';
      document.querySelector('#modalTitle').textContent = 'Tambah produk baru';
      dialog.showModal();
    });
    document.querySelectorAll('.edit-product').forEach(button => {
      button.addEventListener('click', () => {
        const product = JSON.parse(button.dataset.product);
        form.reset();
        ['id', 'name', 'sku', 'category', 'price', 'cost', 'stock', 'min_stock', 'emoji', 'image'].forEach(key => {
          form.elements[key].value = product[key];
        });
        document.querySelector('#modalTitle').textContent = 'Edit produk';
        dialog.showModal();
      });
    });
    document.querySelectorAll('.modal-close').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    document.querySelectorAll('.delete-form').forEach(form => {
      form.addEventListener('submit', event => {
        if (!window.confirm('Hapus produk ini? Riwayat penjualannya tetap tersimpan.')) event.preventDefault();
      });
    });
  }

  if (page === 'pos') {
    const cards = [...document.querySelectorAll('.pos-product')];
    const cart = new Map();
    const search = document.querySelector('#posSearch');
    const itemsElement = document.querySelector('#cartItems');
    const errorElement = document.querySelector('#checkoutError');
    const method = document.querySelector('#paymentMethod');
    const paid = document.querySelector('#paidInput');
    const button = document.querySelector('#checkoutBtn');
    let category = 'all';
    let total = 0;

    function escapeHtml(value) {
      return String(value).replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
    }
    function error(message) {
      errorElement.textContent = message;
      errorElement.hidden = !message;
    }
    function filterCards() {
      const term = search.value.trim().toLocaleLowerCase('id-ID');
      let visible = 0;
      cards.forEach(card => {
        const show = (category === 'all' || card.dataset.category === category) && card.dataset.search.includes(term);
        card.hidden = !show;
        if (show) visible++;
      });
      document.querySelector('#posCount').textContent = visible + ' produk tersedia';
      document.querySelector('#posEmpty').hidden = visible !== 0;
    }
    function updateChange() {
      const change = method.value === 'Tunai' ? Math.max(0, (Number(paid.value) || 0) - total) : 0;
      document.querySelector('#changeAmount').textContent = rupiah(change);
    }
    function render() {
      const products = [...cart.values()];
      const qty = products.reduce((sum, product) => sum + product.quantity, 0);
      total = products.reduce((sum, product) => sum + Number(product.price) * product.quantity, 0);
      document.querySelector('#cartBadge').textContent = qty;
      document.querySelector('#cartQty').textContent = '(' + qty + ' item)';
      document.querySelector('#subtotal').textContent = rupiah(total);
      document.querySelector('#cartTotal').textContent = rupiah(total);
      button.disabled = !products.length;
      if (!products.length) {
        itemsElement.innerHTML = '<div class="empty-cart"><span>🛒</span><strong>Keranjang masih kosong</strong><p>Pilih produk di sebelah kiri untuk memulai transaksi.</p></div>';
      } else {
        itemsElement.innerHTML = products.map(p => `<div class="cart-row"><span class="cart-row-emoji">${escapeHtml(p.emoji)}${fotoTag(p)}</span><div class="cart-row-detail"><strong>${escapeHtml(p.name)}</strong><small>${rupiah(Number(p.price) * p.quantity)}</small></div><div class="quantity-stepper"><button type="button" data-id="${p.id}" data-delta="-1" aria-label="Kurangi ${escapeHtml(p.name)}">−</button><span>${p.quantity}</span><button type="button" data-id="${p.id}" data-delta="1" aria-label="Tambah ${escapeHtml(p.name)}">+</button></div></div>`).join('');
      }
      updateChange();
    }
    search.addEventListener('input', filterCards);
    document.querySelectorAll('#posCategories .category-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        document.querySelector('#posCategories .active')?.classList.remove('active');
        tab.classList.add('active');
        category = tab.dataset.category;
        filterCards();
      });
    });
    cards.forEach(card => card.addEventListener('click', () => {
      const product = JSON.parse(card.dataset.product);
      const current = cart.get(Number(product.id));
      if ((current?.quantity || 0) >= Number(product.stock)) {
        error('Stok ' + product.name + ' tidak mencukupi.');
        return;
      }
      error('');
      if (current) current.quantity++;
      else cart.set(Number(product.id), {...product, quantity: 1});
      render();
    }));
    itemsElement.addEventListener('click', event => {
      const step = event.target.closest('[data-delta]');
      if (!step) return;
      const id = Number(step.dataset.id);
      const product = cart.get(id);
      if (!product) return;
      const next = product.quantity + Number(step.dataset.delta);
      if (next > Number(product.stock)) { error('Stok ' + product.name + ' tidak mencukupi.'); return; }
      if (next <= 0) cart.delete(id);
      else product.quantity = next;
      error('');
      render();
    });
    method.addEventListener('change', () => {
      document.querySelector('#paidField').hidden = method.value !== 'Tunai';
      document.querySelector('.change-row').hidden = method.value !== 'Tunai';
      error('');
      updateChange();
    });
    paid.addEventListener('input', updateChange);
    button.addEventListener('click', async () => {
      if (!cart.size) return;
      if (method.value === 'Tunai' && (paid.value === '' || Number(paid.value) < total)) {
        error('Masukkan uang diterima yang cukup untuk membayar belanja.');
        paid.focus();
        return;
      }
      error('');
      button.disabled = true;
      button.textContent = 'Menyimpan transaksi...';
      const data = new FormData();
      data.set('csrf', document.querySelector('meta[name="csrf-token"]').content);
      data.set('action', 'sale_create');
      data.set('items', JSON.stringify([...cart.values()].map(p => ({id: Number(p.id), quantity: p.quantity}))));
      data.set('customer', document.querySelector('#customerInput').value);
      data.set('payment_method', method.value);
      data.set('paid', method.value === 'Tunai' ? paid.value : String(total));
      try {
        const response = await fetch('index.php', {method: 'POST', body: data, credentials: 'same-origin'});
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.message || 'Transaksi gagal disimpan.');
        window.location.href = result.url;
      } catch (err) {
        error(err.message || 'Koneksi bermasalah. Coba lagi.');
        button.disabled = false;
        button.innerHTML = 'Proses pembayaran &rarr;';
      }
    });
  }
})();
