document.addEventListener("DOMContentLoaded", function () {
  let currentModel = window.INITIAL_MODEL || "";
  let currentPage = 1;
  let allModels = [];
  let filteredModels = [];

  const modelsList = document.getElementById("models-list");
  const coasterList = document.getElementById("coaster-list");
  const modelTitle = document.getElementById("current-model-title");
  const modelSubtitle = document.getElementById("current-model-subtitle");
  const coasterCount = document.getElementById("coaster-count");
  const searchInput = document.getElementById("model-search");
  const searchResults = document.getElementById("search-results");
  const searchIcon = document.getElementById("search-icon");
  const manufacterFilter = document.getElementById("model-manufacter-filter");
  
  const sortFilter = document.getElementById("sort-filter");
  const sortDirInput = document.getElementById("sort-direction");
  const sortDirBtn = document.getElementById("sort-direction-btn");

  // Load saved preferences
  const savedSort = localStorage.getItem("models_sort");
  const savedDir = localStorage.getItem("models_sort_dir");
  if (savedSort && sortFilter) sortFilter.value = savedSort;
  if (savedDir && sortDirInput && sortDirBtn) {
      sortDirInput.value = savedDir;
      sortDirBtn.innerHTML = savedDir === "ASC" 
          ? '<i class="fa-solid fa-arrow-up-short-wide"></i>' 
          : '<i class="fa-solid fa-arrow-down-wide-short"></i>';
  }

  // Initial load
  loadModels();
  if (currentModel) {
    loadCoasters(1, currentModel);
  } else {
    coasterList.innerHTML = `<div class="text-center text-muted p-5"><i class="fa-solid fa-arrow-left fs-1 mb-3"></i><br>Selecciona un modelo de la lista para ver sus montañas rusas.</div>`;
  }

  function loadModels() {
    fetch(`${BASE_URL}/api/php/coasters.php?action=models_list`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          allModels = data.models;
          filteredModels = [...allModels];
          
          // Populate manufacturer filter
          const manufacters = [...new Set(allModels.map(m => m.manufacter).filter(Boolean))].sort();
          manufacters.forEach(m => {
            const option = document.createElement("option");
            option.value = m;
            option.textContent = m;
            manufacterFilter.appendChild(option);
          });

          renderModelsList();
        }
      })
      .catch((e) => console.warn("Error loadModels:", e));
  }

  function renderModelsList() {
    modelsList.innerHTML = "";
    if (filteredModels.length === 0) {
      modelsList.innerHTML = `<div class="text-center text-muted py-3">No se encontraron modelos.</div>`;
      return;
    }
    
    filteredModels.forEach((m) => {
      const a = document.createElement("a");
      a.href = "#";
      a.className = `list-group-item list-group-item-action d-flex justify-content-between align-items-center bg-dark text-white border-secondary rounded-0 ${m.model === currentModel ? 'active border-success' : ''}`;
      a.innerHTML = `
        <div class="d-flex flex-column min-w-0" style="overflow: hidden; text-overflow: ellipsis;">
            <span class="fw-bold text-truncate" title="${m.model}">${m.model}</span>
            <small class="text-muted text-truncate" title="${m.manufacter || 'Desconocido'}">${m.manufacter || 'Desconocido'}</small>
        </div>
        <span class="badge bg-success rounded-pill ms-2">${m.coasters_count}</span>
      `;
      a.addEventListener("click", (e) => {
        e.preventDefault();
        selectModel(m.model);
      });
      modelsList.appendChild(a);
    });
  }

  function selectModel(modelName) {
    currentModel = modelName;
    modelTitle.textContent = modelName;
    modelSubtitle.textContent = "Descubre todas las montañas rusas de este modelo.";
    
    // Update active class in sidebar
    const items = modelsList.querySelectorAll("a");
    items.forEach(a => {
        a.classList.remove("active", "border-success");
        if (a.querySelector(".fw-bold").textContent === modelName) {
            a.classList.add("active", "border-success");
        }
    });

    // Update URL without reloading
    const newUrl = new URL(window.location);
    newUrl.searchParams.set("model", modelName);
    window.history.pushState({}, "", newUrl);

    loadCoasters(1, modelName);
  }

  manufacterFilter.addEventListener("change", function () {
    const val = this.value;
    if (val) {
        filteredModels = allModels.filter(m => m.manufacter === val);
    } else {
        filteredModels = [...allModels];
    }
    renderModelsList();
  });

  // Load coasters logic (adapted from coasters.js)
  function loadCoasters(page, modelName) {
    currentPage = page;
    coasterList.innerHTML = `<div class="text-center text-muted p-5"><i class="fa-solid fa-spinner fa-spin fs-2"></i></div>`;
    
    const sort = document.getElementById("sort-filter").value || "id";
    const orderDir = document.getElementById("sort-direction").value || "DESC";

    const params = new URLSearchParams({
      action: "apply_filters",
      page,
      model: modelName,
      sort,
      order_dir: orderDir,
    });

    fetch(`${BASE_URL}/api/php/coasters.php?${params}`)
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          let totalMsg = data.total || 0;
          let tKey = totalMsg === 1 ? 'coasters.search.showing_one' : 'coasters.search.showing_many';
          let tMsg = window.t && window.t(tKey) ? window.t(tKey) : tKey;
          if (tMsg === tKey) tMsg = `Mostrando ${totalMsg} montaña${totalMsg !== 1 ? "s" : ""} rusa${totalMsg !== 1 ? "s" : ""}`;
          coasterCount.textContent = tMsg.replace('{total}', totalMsg);
          
          displayCoasters(data.coasters);
          displayPagination(data.total, page);
        } else {
          coasterList.innerHTML = "<p style='color:red;'>Error cargando montañas rusas</p>";
        }
      })
      .catch((e) => console.warn("Error loadCoasters:", e));
  }

  function displayCoasters(coasters) {
    coasterList.innerHTML = "";
    if (!coasters || coasters.length === 0) {
        coasterList.innerHTML = `<div class="text-center text-muted p-5">No hay montañas rusas registradas para este modelo.</div>`;
        return;
    }
    
    coasters.forEach(function (coaster) {
      const coasterCard = document.createElement("a");
      coasterCard.href = BASE_URL + `/web/views/public/coasters/coasters.php?id=${coaster.id}`;
      coasterCard.classList.add(
        "list-group-item",
        "list-group-item-action",
        "d-flex",
        "align-items-center",
        "p-3",
        "bg-dark",
        "text-white",
        "border-secondary"
      );

      let validImgUrl = coaster.imagen_url;
      if (validImgUrl && !validImgUrl.startsWith("http")) {
        validImgUrl = BASE_URL + (validImgUrl.startsWith("/") ? "" : "/") + validImgUrl;
      }
      
      // Evitar cargar la URL rota específica que da error 400
      if (validImgUrl && validImgUrl.includes("aaipdic")) {
          validImgUrl = null;
      }

      const img = validImgUrl
        ? `<img src="${validImgUrl}" alt="${coaster.coaster_name}" class="rounded-0 shadow-sm" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='https://www.hussrides.com/fileadmin/_processed_/5/e/csm_giant-frisbee-cedarpoint-01_0697df513a.jpg';" style="width: 110px; height: 110px; object-fit: cover; margin-right: 20px; flex-shrink:0;">`
        : `<img src="https://www.hussrides.com/fileadmin/_processed_/5/e/csm_giant-frisbee-cedarpoint-01_0697df513a.jpg" alt="Sin imagen" class="rounded-0 shadow-sm" style="width: 110px; height: 110px; object-fit: cover; margin-right: 20px; flex-shrink:0;">`;

      const _unk = "Desconocido";
      const manufacter = coaster.manufacter || _unk;
      const modelo = coaster.modelo || _unk;
      const year = coaster.opening_year || "N/A";
      const scoreText = parseFloat(coaster.score || 0).toFixed(2) + " ★";

      coasterCard.innerHTML = `
        ${img}
        <div class="flex-grow-1 min-w-0">
          <h5 class="mb-1 fw-bold text-success text-truncate" style="font-size: 1.25rem;">${coaster.coaster_name}</h5>
          <p class="mb-1 text-muted text-truncate"><i class="fa-solid fa-map-pin me-1"></i>${coaster.park_name}</p>
          <small class="text-secondary text-truncate d-block">${manufacter} • ${modelo} • ${year} • ${scoreText}</small>
        </div>
        <i class="fa-solid fa-chevron-right text-muted ms-3 flex-shrink-0"></i>
      `;
      coasterList.appendChild(coasterCard);
    });
  }

  function displayPagination(total, page) {
    const pagination = document.getElementById("pagination");
    pagination.innerHTML = "";
    if (total <= 15) return;

    const pageBtn = document.createElement("div");
    pageBtn.classList.add("page-buttons", "d-flex", "flex-nowrap", "justify-content-center", "gap-1");
    
    const totalPages = Math.ceil(total / 15);
    
    const prevBtn = document.createElement("button");
    prevBtn.className = "btn btn-outline-success rounded-0";
    prevBtn.textContent = "«";
    if (page === 1) prevBtn.disabled = true;
    prevBtn.onclick = () => { if (page > 1) loadCoasters(page - 1, currentModel); };
    pageBtn.appendChild(prevBtn);

    let start = Math.max(1, page - 2);
    let end = Math.min(totalPages, page + 2);
    
    for (let i = start; i <= end; i++) {
        const btn = document.createElement("button");
        btn.className = `btn rounded-0 ${i === page ? 'btn-success fw-bold' : 'btn-outline-success'}`;
        btn.textContent = i;
        btn.onclick = () => { if (i !== page) loadCoasters(i, currentModel); };
        pageBtn.appendChild(btn);
    }

    const nextBtn = document.createElement("button");
    nextBtn.className = "btn btn-outline-success rounded-0";
    nextBtn.textContent = "»";
    if (page === totalPages) nextBtn.disabled = true;
    nextBtn.onclick = () => { if (page < totalPages) loadCoasters(page + 1, currentModel); };
    pageBtn.appendChild(nextBtn);

    pagination.appendChild(pageBtn);
  }

  // Sort events
  sortFilter.addEventListener("change", function () {
    if (this.value === "name" || this.value === "year" || this.value === "id") {
        sortDirInput.value = "ASC";
        sortDirBtn.innerHTML = '<i class="fa-solid fa-arrow-up-short-wide"></i>';
    } else {
        sortDirInput.value = "DESC";
        sortDirBtn.innerHTML = '<i class="fa-solid fa-arrow-down-wide-short"></i>';
    }
    
    localStorage.setItem("models_sort", this.value);
    localStorage.setItem("models_sort_dir", sortDirInput.value);
    
    if (currentModel) loadCoasters(1, currentModel);
  });
  
  sortDirBtn.addEventListener("click", () => {
    if (sortDirInput.value === "ASC") {
        sortDirInput.value = "DESC";
        sortDirBtn.innerHTML = '<i class="fa-solid fa-arrow-down-wide-short"></i>';
    } else {
        sortDirInput.value = "ASC";
        sortDirBtn.innerHTML = '<i class="fa-solid fa-arrow-up-short-wide"></i>';
    }
    
    localStorage.setItem("models_sort_dir", sortDirInput.value);
    
    if (currentModel) loadCoasters(1, currentModel);
  });


  // Search logic for models on the right
  searchInput.addEventListener("input", function () {
    const query = this.value.trim().toLowerCase();
    
    if (query.length > 0) {
        searchIcon.classList.replace("fa-magnifying-glass", "fa-xmark");
        searchIcon.classList.replace("text-muted", "text-danger");
        searchIcon.style.cursor = "pointer";
        
        const results = allModels.filter(m => m.model.toLowerCase().includes(query) || (m.manufacter && m.manufacter.toLowerCase().includes(query)));
        
        searchResults.innerHTML = "";
        if (results.length > 0) {
            results.slice(0, 15).forEach(m => {
                const a = document.createElement("a");
                a.href = "#";
                a.className = "list-group-item list-group-item-action bg-dark text-white border-secondary";
                a.innerHTML = `<span class="fw-bold">${m.model}</span> <small class="text-muted ms-2">${m.manufacter || ''}</small>`;
                a.onclick = (e) => {
                    e.preventDefault();
                    selectModel(m.model);
                    searchResults.style.display = "none";
                    searchInput.value = m.model;
                };
                searchResults.appendChild(a);
            });
        } else {
            searchResults.innerHTML = `<div class="list-group-item bg-dark text-muted">No se encontraron modelos.</div>`;
        }
        searchResults.style.display = "block";
    } else {
        searchIcon.classList.replace("fa-xmark", "fa-magnifying-glass");
        searchIcon.classList.replace("text-danger", "text-muted");
        searchIcon.style.cursor = "text";
        searchResults.style.display = "none";
    }
  });

  searchIcon.addEventListener("click", function () {
    if (this.classList.contains("fa-xmark")) {
      searchInput.value = "";
      searchResults.style.display = "none";
      this.classList.replace("fa-xmark", "fa-magnifying-glass");
      this.classList.replace("text-danger", "text-muted");
      this.style.cursor = "text";
    }
  });

  // Hide search results when clicking outside
  document.addEventListener("click", function (e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
      searchResults.style.display = "none";
    }
  });

});
