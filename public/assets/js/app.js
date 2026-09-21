(() => {
  const header = document.querySelector('#site-header');
  const mobile = document.querySelector('#mobile-navigation');
  const toggle = document.querySelector('#menu-toggle');
  const updateHeader = () => {
    header?.classList.toggle('is-scrolled', window.scrollY > 50);
    document.querySelector('#floating-widgets')?.classList.toggle('hidden', window.scrollY <= 480);
    document.querySelector('#floating-widgets')?.classList.toggle('flex', window.scrollY > 480);
  };
  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });
  toggle?.addEventListener('click', () => {
    const open = mobile?.classList.toggle('hidden') === false;
    mobile?.classList.toggle('flex', open);
    toggle.setAttribute('aria-expanded', String(open));
  });
  document.querySelector('[data-scroll-top]')?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  const dialog = document.querySelector('#image-dialog');
  const closeDialog = () => { if (dialog) dialog.hidden = true; };
  document.addEventListener('click', event => {
    const preview = event.target.closest('[data-image-preview]');
    if (preview && dialog) {
      dialog.querySelector('img').src = preview.dataset.imagePreview;
      dialog.hidden = false;
    }
    if (event.target.closest('[data-dialog-close]') || event.target === dialog) closeDialog();
  });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') closeDialog(); });

  const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
  const resultCard = item => {
    const models = [...new Set([...(item.matchedModels || []), ...(item.compatibleModels || [])])];
    const matched = new Set(item.matchedModels || []);
    const images = (item.images || []).slice(0, 2).map((image, index) => `<button type="button" data-image-preview="${escapeHtml(image)}" class="relative min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-white"><img src="${escapeHtml(image)}" alt="Ảnh bao bì ${index + 1} của mã ${escapeHtml(item.sku)}" class="h-full w-full object-contain p-0.5"></button>`).join('');
    const types = (item.glassTypes || []).map(type => `<span class="rounded-full px-2 py-0.5 text-[10px] font-bold ${type === 'privacy' ? 'bg-amber-100 text-amber-800' : 'bg-primary-100 text-primary-700'}">${type === 'privacy' ? 'Chống nhìn trộm' : type === 'standard' ? 'Kính trong' : 'Chưa phân loại'}</span>`).join('');
    const chips = models.map(model => `<span class="rounded-lg border px-2 py-1 text-[11px] font-medium ${matched.has(model) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600'}">${escapeHtml(model)}</span>`).join('');
    return `<article class="lookup-result min-h-[202px] rounded-2xl border border-slate-200 bg-slate-50 p-3.5 lg:min-h-[162px]"><div class="flex gap-3">${images ? `<div class="grid h-28 w-28 shrink-0 gap-1.5 sm:h-[132px] sm:w-36 ${item.images.length > 1 ? 'grid-cols-2' : 'grid-cols-1'}">${images}</div>` : ''}<div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-2"><div class="min-w-0"><strong class="block truncate text-base tracking-wide text-slate-950">${escapeHtml(item.sku)}</strong><p class="mt-0.5 line-clamp-1 text-xs text-slate-600">${escapeHtml(item.name)}</p></div><span class="text-emerald-600">✓</span></div><div class="mt-2 flex flex-wrap gap-1.5">${types}</div><p class="mt-2 text-[11px] font-semibold text-slate-600">Dùng chung với ${models.length} dòng máy:</p><div class="mt-1.5 flex flex-wrap gap-1.5">${chips}</div></div></div></article>`;
  };

  document.querySelectorAll('[data-lookup]').forEach(root => {
    const form = root.querySelector('form');
    const input = root.querySelector('[data-lookup-input]');
    const panel = root.querySelector('[data-lookup-panel]');
    const summary = root.querySelector('[data-lookup-summary]');
    const list = root.querySelector('[data-lookup-results]');
    const error = root.querySelector('[data-lookup-error]');
    let request;
    const search = async value => {
      const query = String(value || '').trim();
      if (query.length < 2) { error.textContent = 'Nhập ít nhất 2 ký tự để tra cứu.'; error.hidden = false; return; }
      error.hidden = true; panel.hidden = false; list.innerHTML = '<div class="rounded-2xl bg-slate-50 p-5 text-sm">Đang tra cứu...</div>';
      request?.abort(); request = new AbortController();
      try {
        const response = await fetch(`/api/glass-lookup?q=${encodeURIComponent(query)}&brand=all&type=all&limit=50`, { signal: request.signal });
        const body = await response.json();
        if (!response.ok) throw new Error(body.error || 'Không thể tra cứu lúc này.');
        summary.innerHTML = `<strong>Kết quả cho “${escapeHtml(query)}”</strong><span class="block text-xs text-slate-500">${body.total} mã kính phù hợp</span>`;
        list.innerHTML = body.items?.length ? body.items.map(resultCard).join('') : '<div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">Chưa tìm thấy mã phù hợp.</div>';
      } catch (cause) {
        if (cause.name !== 'AbortError') list.innerHTML = `<div class="rounded-2xl bg-red-50 p-4 text-sm text-red-700">${escapeHtml(cause.message)}</div>`;
      }
    };
    form?.addEventListener('submit', event => { event.preventDefault(); search(input.value); });
    root.querySelectorAll('[data-lookup-example]').forEach(button => button.addEventListener('click', () => { input.value = button.dataset.lookupExample; search(input.value); }));
  });

  document.querySelectorAll('[data-contact-form]').forEach(form => form.addEventListener('submit', async event => {
    event.preventDefault();
    const status = form.querySelector('[data-form-status]');
    const button = form.querySelector('button[type=submit]');
    button.disabled = true; status.textContent = 'Đang gửi...';
    const payload = Object.fromEntries(new FormData(form));
    payload.requestId ||= crypto.randomUUID();
    try {
      const response = await fetch('/api/contact', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':window.OUFEIDUN.csrf}, body:JSON.stringify(payload) });
      const body = await response.json();
      if (!response.ok) throw new Error(body.error || 'Không thể gửi yêu cầu.');
      status.textContent = body.message; status.className = 'mt-3 text-sm font-medium text-emerald-700'; form.reset();
    } catch (cause) { status.textContent = cause.message; status.className = 'mt-3 text-sm font-medium text-red-700'; }
    finally { button.disabled = false; }
  }));
})();

(() => {
  const headers = { 'Content-Type':'application/json', 'X-CSRF-Token':window.OUFEIDUN?.csrf || '' };
  const lines = value => String(value || '').split(/\r?\n/).map(item => item.trim()).filter(Boolean);
  const uploadFiles = async files => {
    const paths=[];
    for(const file of files){const body=new FormData();body.append('image',file);const response=await fetch('/api/admin/images',{method:'POST',headers:{'X-CSRF-Token':window.OUFEIDUN.csrf},body});const result=await response.json();if(!response.ok)throw new Error(result.error||'Không thể tải ảnh.');paths.push(result.path);}
    return paths;
  };

  const productRoot=document.querySelector('[data-product-admin]');
  if(productRoot){
    const dialog=productRoot.querySelector('[data-product-dialog]');const form=productRoot.querySelector('[data-product-form]');const status=form.querySelector('[data-dialog-status]');
    const fill=product=>{form.reset();form.elements.id.value=product?.id||'';for(const field of ['name','slug','category','description','badge','image','sortOrder'])if(form.elements[field])form.elements[field].value=product?.[field]??(field==='category'?'Kính cường lực':field==='sortOrder'?0:'');form.elements.features.value=(product?.features||[]).join('\n');form.elements.specifications.value=(product?.specifications||[]).map(item=>`${item.label} | ${item.value}`).join('\n');form.elements.gallery.value=(product?.gallery||[]).join('\n');for(const field of ['published','featured','showFactory'])form.elements[field].checked=Boolean(product?.[field]);status.textContent='';dialog.showModal();};
    productRoot.querySelector('[data-product-new]')?.addEventListener('click',()=>fill(null));
    productRoot.querySelectorAll('[data-product-edit]').forEach(button=>button.addEventListener('click',()=>fill(JSON.parse(button.dataset.productEdit))));
    productRoot.querySelector('[data-product-close]')?.addEventListener('click',()=>dialog.close());
    form.querySelector('[data-upload-target=image]')?.addEventListener('change',async event=>{try{status.textContent='Đang tải ảnh...';const paths=await uploadFiles(event.target.files);if(paths[0])form.elements.image.value=paths[0];status.textContent='Đã tải ảnh.';}catch(error){status.textContent=error.message;}});
    form.addEventListener('submit',async event=>{event.preventDefault();status.textContent='Đang lưu...';const id=form.elements.id.value;const payload={name:form.elements.name.value,slug:form.elements.slug.value,category:form.elements.category.value,description:form.elements.description.value,badge:form.elements.badge.value,image:form.elements.image.value,features:lines(form.elements.features.value),specifications:lines(form.elements.specifications.value).map(line=>{const[label,...rest]=line.split('|');return{label:label.trim(),value:rest.join('|').trim()};}).filter(item=>item.label&&item.value),gallery:lines(form.elements.gallery.value),published:form.elements.published.checked,featured:form.elements.featured.checked,showFactory:form.elements.showFactory.checked,sortOrder:Number(form.elements.sortOrder.value)||0};try{const response=await fetch(id?`/api/admin/products/${id}`:'/api/admin/products',{method:id?'PUT':'POST',headers,body:JSON.stringify(payload)});const result=await response.json();if(!response.ok)throw new Error(result.error||'Không thể lưu sản phẩm.');location.reload();}catch(error){status.textContent=error.message;}});
    productRoot.querySelectorAll('[data-product-delete]').forEach(button=>button.addEventListener('click',async()=>{if(!confirm('Xóa sản phẩm này?'))return;const response=await fetch(`/api/admin/products/${button.dataset.productDelete}`,{method:'DELETE',headers});const result=await response.json();if(response.ok)location.reload();else alert(result.error||'Không thể xóa.');}));
  }

  const lookupRoot=document.querySelector('[data-lookup-admin]');
  if(lookupRoot){
    const dialog=lookupRoot.querySelector('[data-lookup-dialog]');const form=lookupRoot.querySelector('[data-lookup-form]');const status=form.querySelector('[data-dialog-status]');let items=[];
    const load=async()=>{const response=await fetch('/api/admin/glass-lookup');const result=await response.json();if(response.ok)items=result.items||[];};load();
    const fill=item=>{form.reset();form.elements.id.value=item?.id||'';form.elements.sku.value=item?.sku||'';form.elements.name.value=item?.name||'';form.elements.description.value=item?.description||'';form.elements.models.value=(item?.models||[]).map(model=>`${model.name} | ${model.glassType}`).join('\n');form.elements.images.value=(item?.images||[]).join('\n');form.elements.active.checked=item?Boolean(item.active):true;form.querySelector('[data-lookup-delete]').classList.toggle('hidden',!item);status.textContent='';dialog.showModal();};
    lookupRoot.querySelector('[data-lookup-new]')?.addEventListener('click',()=>fill(null));
    lookupRoot.querySelectorAll('[data-lookup-open]').forEach(button=>button.addEventListener('click',async()=>{if(!items.length)await load();fill(items.find(item=>String(item.id)===button.dataset.lookupOpen));}));
    lookupRoot.querySelector('[data-lookup-close]')?.addEventListener('click',()=>dialog.close());
    form.querySelector('[data-upload-target=images]')?.addEventListener('change',async event=>{try{status.textContent='Đang tải ảnh...';const files=Array.from(event.target.files).slice(0,2);const paths=await uploadFiles(files);form.elements.images.value=[...lines(form.elements.images.value),...paths].slice(0,2).join('\n');status.textContent='Đã tải ảnh.';}catch(error){status.textContent=error.message;}});
    form.addEventListener('submit',async event=>{event.preventDefault();const id=form.elements.id.value;const models=lines(form.elements.models.value).map(line=>{const[name,type='standard']=line.split('|').map(value=>value.trim());return{name,glassType:['standard','privacy','unknown'].includes(type)?type:'standard'};});const payload={sku:form.elements.sku.value,name:form.elements.name.value,description:form.elements.description.value,models,images:lines(form.elements.images.value).slice(0,2),active:form.elements.active.checked};status.textContent='Đang lưu...';try{const response=await fetch(id?`/api/admin/glass-lookup/${id}`:'/api/admin/glass-lookup',{method:id?'PUT':'POST',headers,body:JSON.stringify(payload)});const result=await response.json();if(!response.ok)throw new Error(result.error||'Không thể lưu mã kính.');location.reload();}catch(error){status.textContent=error.message;}});
    form.querySelector('[data-lookup-delete]')?.addEventListener('click',async()=>{const id=form.elements.id.value;if(!id||!confirm('Xóa mã kính này?'))return;const response=await fetch(`/api/admin/glass-lookup/${id}`,{method:'DELETE',headers});const result=await response.json();if(response.ok)location.reload();else status.textContent=result.error||'Không thể xóa.';});
  }
})();
