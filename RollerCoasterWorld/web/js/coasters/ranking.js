// ranking.js — Ranking global de coasters

$(document).ready(function () {
  const coasterList = document.querySelector("#coaster-list");
  const paginationEl = document.querySelector("#pagination");
  const ITEMS_PER_PAGE = 15;
  let currentPage = 1;

  function loadCoasters(page = 1) {
    currentPage = page;
    coasterList.innerHTML = `
      <div class="text-center py-5">
        <div class="spinner-border text-success mb-3" role="status"></div>
        <p class="text-muted">Cargando el ranking global...</p>
      </div>`;

    fetch(`${BASE_URL}/api/php/coasters.php?action=ranking&page=${page}`)
      .then((r) => r.json())
      .then((data) => {
        if (!data.coasters || data.coasters.length === 0) {
          coasterList.innerHTML = `<p class="text-center text-muted py-5">No hay resultados.</p>`;
          return;
        }
        const offset = (page - 1) * ITEMS_PER_PAGE;
        displayCoasters(data.coasters, offset);
        displayPagination(data.total, page);
      })
      .catch(() => {
        coasterList.innerHTML = `<p style="color:red;">Error cargando el ranking.</p>`;
      });
  }

  function displayCoasters(coasters, offset = 0) {
    coasterList.innerHTML = "";
    coasters.forEach(function (coaster, i) {
      const position = offset + i + 1;

      let validImgUrl = coaster.imagen_url;
      if (validImgUrl && !validImgUrl.startsWith("http")) {
        validImgUrl =
          BASE_URL + (validImgUrl.startsWith("/") ? "" : "/") + validImgUrl;
      }
      const img = validImgUrl
        ? `<img src="${validImgUrl}" alt="${coaster.coaster_name}" class="rounded-0 shadow-sm" referrerpolicy="no-referrer" style="width:100px; height:100px; object-fit:cover; margin-right:20px;">`
        : `<img src="https://www.hussrides.com/fileadmin/_processed_/5/e/csm_giant-frisbee-cedarpoint-01_0697df513a.jpg" alt="Sin imagen" class="rounded-0 shadow-sm" style="width:100px; height:100px; object-fit:cover; margin-right:20px;">`;

      const manufacter = coaster.manufacter || null;
      const modelo = coaster.modelo || null;
      const year = coaster.opening_year || null;
      const starsVal = parseFloat(coaster.stars || 0);

      // Solo mostrar partes que no son null
      const infoParts = [manufacter, modelo, year].filter(Boolean);
      const infoLine = infoParts.join(" • ");

      // Color del podio
      const podiumColor =
        position === 1
          ? "#FFD700"
          : position === 2
            ? "#C0C0C0"
            : position === 3
              ? "#CD7F32"
              : "#6e7681";

      const card = document.createElement("a");
      card.href =
        BASE_URL + `/web/views/public/coasters/coasters.php?id=${coaster.id}`;
      card.classList.add(
        "list-group-item",
        "list-group-item-action",
        "d-flex",
        "flex-column",
        "flex-md-row",
        "align-items-center",
        "p-3",
        "gap-3",
      );
      card.innerHTML = `
        <div class="d-flex align-items-center flex-shrink-0" style="min-width: 140px;">
          <div class="text-center me-3" style="width:35px;">
            <span class="fw-black" style="font-size:1.2rem; color:${podiumColor};">#${position}</span>
          </div>
          <div class="flex-shrink-0">
            <img src="${validImgUrl || "https://www.hussrides.com/fileadmin/_processed_/5/e/csm_giant-frisbee-cedarpoint-01_0697df513a.jpg"}" 
                 alt="${coaster.coaster_name}" 
                 class="rounded shadow-sm" 
                 referrerpolicy="no-referrer" 
                 style="width:80px; height:80px; object-fit:cover; border: 1px solid rgba(255,255,255,0.1);">
          </div>
        </div>

        <div class="flex-grow-1 px-3 min-w-0 text-start">
          <h5 class="mb-1 fw-bold text-white text-truncate" style="font-family: var(--rcw-font-title); font-size: 1.15rem; letter-spacing: -0.01em;">${coaster.coaster_name}</h5>
          <p class="mb-1 text-muted small text-truncate"><i class="fa-solid fa-map-pin me-1 opacity-50"></i>${coaster.park_name}</p>
          ${infoLine ? `<small class="text-secondary opacity-75 d-block text-truncate">${infoLine}</small>` : ""}
        </div>

        <div class="flex-shrink-0 d-flex align-items-center justify-content-end gap-4" style="min-width: 100px;">
          ${
            starsVal > 0
              ? `
            <div class="d-flex align-items-center gap-1">
              <span class="fw-bold text-warning" style="font-size:1.15rem;">${starsVal.toFixed(2)}</span>
              <i class="fa-solid fa-star text-warning" style="font-size:0.95rem;"></i>
            </div>
          `
              : ""
          }
          <i class="fa-solid fa-chevron-right text-muted opacity-25 d-none d-md-block ms-2"></i>
        </div>
      `;
      coasterList.appendChild(card);
    });
  }

  function displayPagination(total, page) {
    paginationEl.innerHTML = "";
    const pageBtn = document.createElement("div");
    pageBtn.classList.add("page-buttons");
    const totalPages = Math.ceil(total / ITEMS_PER_PAGE);
    if (totalPages <= 1) return;

    const prevBtn = document.createElement("button");
    prevBtn.className = "btn btn-outline-success mx-1";
    prevBtn.textContent = "«";
    if (page === 1) prevBtn.disabled = true;
    prevBtn.addEventListener("click", () => {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadCoasters(page - 1);
    });
    pageBtn.appendChild(prevBtn);

    const btnFirst = document.createElement("button");
    btnFirst.className =
      page === 1
        ? "btn btn-success mx-1 text-white"
        : "btn btn-light text-success border mx-1";
    btnFirst.textContent = "1";
    btnFirst.addEventListener("click", () => {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadCoasters(1);
    });
    pageBtn.appendChild(btnFirst);

    const btnDots = document.createElement("button");
    btnDots.className = "btn border-0 text-secondary mx-1";
    btnDots.textContent = "...";
    btnDots.disabled = true;
    pageBtn.appendChild(btnDots);

    let start = Math.max(2, page - 1);
    let end = Math.min(totalPages - 1, start + 2);
    start = Math.max(2, end - 2);
    for (let i = start; i <= end; i++) {
      const pb = document.createElement("button");
      pb.className =
        i === page
          ? "btn btn-success text-white mx-1"
          : "btn btn-light text-success border mx-1";
      pb.textContent = i;
      pb.addEventListener("click", () => {
        window.scrollTo({ top: 10, behavior: "smooth" });
        loadCoasters(i);
      });
      pageBtn.appendChild(pb);
    }

    const btnDots2 = document.createElement("button");
    btnDots2.className = "btn border-0 text-secondary mx-1";
    btnDots2.textContent = "...";
    btnDots2.disabled = true;
    pageBtn.appendChild(btnDots2);

    const btnLast = document.createElement("button");
    btnLast.className =
      page === totalPages
        ? "btn btn-success mx-1 text-white"
        : "btn btn-light text-success border mx-1";
    btnLast.textContent = `${totalPages}`;
    btnLast.addEventListener("click", () => {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadCoasters(totalPages);
    });
    pageBtn.appendChild(btnLast);

    const nextBtn = document.createElement("button");
    nextBtn.className = "btn btn-outline-success mx-1";
    nextBtn.textContent = "»";
    if (page === totalPages) nextBtn.disabled = true;
    nextBtn.addEventListener("click", () => {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadCoasters(page + 1);
    });
    pageBtn.appendChild(nextBtn);

    paginationEl.appendChild(pageBtn);
  }

  loadCoasters(1);
});
