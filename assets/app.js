(function(){
  // Theme handling
  const root = document.documentElement;
  const toggleBtn = document.getElementById('theme-toggle');
  const saved = localStorage.getItem('theme') || 'light';
  root.setAttribute('data-theme', saved);
  if (toggleBtn) updateToggleBtn(toggleBtn, saved);

  function updateToggleBtn(btn, theme){
    if (theme === 'dark') {
      btn.textContent = 'Light';
      btn.classList.add('btn-dark-mode');
      btn.classList.remove('btn-light');
    } else {
      btn.textContent = 'Dark';
      btn.classList.remove('btn-dark-mode');
      if (!btn.classList.contains('btn-light')) btn.classList.add('btn-light');
    }
  }

  toggleBtn?.addEventListener('click', function(){
    const current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    updateToggleBtn(toggleBtn, next);
  });

  // Toast autohide (3s is set in markup)
  const toastEl = document.getElementById('appToast');
  if (toastEl && window.bootstrap) {
    new bootstrap.Toast(toastEl).show();
  }

  // Generic cropper setup
  function setupCropper(ids){
    const fileInput = document.getElementById(ids.file);
    const cropArea = document.getElementById(ids.area);
    const img = document.getElementById(ids.img);
    const preview = document.getElementById(ids.preview);
    const applyBtn = document.getElementById(ids.apply);
    const cancelBtn = document.getElementById(ids.cancel);
    const hidden = document.getElementById(ids.hidden);
    if (!fileInput || !img || !preview || !hidden || !window.Cropper) return;

    let cropper = null;

    function resetCropper(){
      hidden.value = '';
      if (cropper) { cropper.destroy(); cropper = null; }
      img.src = '';
      preview.style.backgroundImage = '';
      if (cropArea) cropArea.style.display = 'none';
    }

    fileInput.addEventListener('change', function(){
      const file = this.files && this.files[0];
      if (!file) return resetCropper();
      if (!['image/png','image/jpeg','image/webp'].includes(file.type)) {
        alert('Only PNG, JPG, or WEBP images are allowed.');
        this.value = '';
        return;
      }
      if (file.size > 2*1024*1024) { // 2MB
        alert('Image too large (max 2MB).');
        this.value = '';
        return;
      }
      const url = URL.createObjectURL(file);
      img.src = url;
      img.onload = function(){
        if (cropArea) cropArea.style.display = 'block';
        if (cropper) cropper.destroy();
        cropper = new Cropper(img, {
          viewMode: 1,
          aspectRatio: 1,
          dragMode: 'move',
          autoCropArea: 1,
          ready(){
            const canvas = cropper.getCroppedCanvas({ width: ids.size || 160, height: ids.size || 160 });
            preview.style.backgroundImage = 'url(' + canvas.toDataURL('image/png') + ')';
            preview.style.backgroundSize = 'cover';
            preview.style.backgroundPosition = 'center';
          },
          crop(){
            const canvas = cropper.getCroppedCanvas({ width: ids.size || 160, height: ids.size || 160 });
            if (canvas) {
              preview.style.backgroundImage = 'url(' + canvas.toDataURL('image/png') + ')';
            }
          }
        });
      };
    });

    applyBtn?.addEventListener('click', function(){
      if (!cropper) return;
      const canvas = cropper.getCroppedCanvas({ width: 400, height: 400 });
      if (!canvas) return;
      hidden.value = canvas.toDataURL('image/png');
      if (cropArea) cropArea.style.display = 'none';
    });

    cancelBtn?.addEventListener('click', function(){
      fileInput.value = '';
      resetCropper();
    });
  }

  // Initialize cropper on create page
  setupCropper({
    file: 'profile_image',
    area: 'crop-area',
    img: 'cropper-source',
    preview: 'preview-circle',
    apply: 'btn-crop-apply',
    cancel: 'btn-crop-cancel',
    hidden: 'cropped_image',
    size: 160
  });

  // Basic client-side form validation
  function attachAgeValidation(formId){
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', function(e){
      const age = form.age?.valueAsNumber;
      if (age && (age < 1 || age > 120)) {
        e.preventDefault();
        form.classList.add('was-validated');
        alert('Age must be between 1 and 120.');
      }
    });
  }

  attachAgeValidation('create-form');
  attachAgeValidation('edit-form');
})();
