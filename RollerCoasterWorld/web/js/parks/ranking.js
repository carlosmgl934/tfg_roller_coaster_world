// ranking.js — Ranking global de parques

$(document).ready(function () {
  const parkList = document.querySelector("#park-list");
  const paginationEl = document.querySelector("#pagination");
  const ITEMS_PER_PAGE = 15;
  let currentPage = 1;

  function loadParks(page = 1) {
    currentPage = page;
    parkList.innerHTML = `
      <div class="text-center py-5">
        <div class="spinner-border text-success mb-3" role="status"></div>
        <p class="text-muted">Cargando el ranking global...</p>
      </div>`;

    fetch(`${BASE_URL}/api/php/parks.php?action=ranking&page=${page}`)
      .then((r) => r.json())
      .then((data) => {
        if (!data.parks || data.parks.length === 0) {
          parkList.innerHTML = `<p class="text-center text-muted py-5">No hay resultados.</p>`;
          return;
        }
        const offset = (page - 1) * ITEMS_PER_PAGE;
        displayParks(data.parks, offset);
        displayPagination(data.total, page);
      })
      .catch(() => {
        parkList.innerHTML = `<p style="color:red;">Error cargando el ranking.</p>`;
      });
  }

  function displayParks(parks, offset = 0) {
    parkList.innerHTML = "";
    parks.forEach(function (park, i) {
      const position = offset + i + 1;

      let validImgUrl = park.imagen_url;
      if (validImgUrl && !validImgUrl.startsWith("http")) {
        validImgUrl =
          BASE_URL + (validImgUrl.startsWith("/") ? "" : "/") + validImgUrl;
      }
      const img = validImgUrl
        ? `<img src="${validImgUrl}" alt="${park.park_name}" class="rounded-0 shadow-sm" referrerpolicy="no-referrer" style="width:100px; height:100px; object-fit:cover; margin-right:20px;">`
        : `<img src="https://www.hussrides.com/fileadmin/_processed_/5/e/csm_giant-frisbee-cedarpoint-01_0697df513a.jpg" alt="Sin imagen" class="rounded-0 shadow-sm" style="width:100px; height:100px; object-fit:cover; margin-right:20px;">`;

      const country = park.park_country || null;
      const year = park.opening_year || null;
      const num_coasters = park.operating_coasters || park.num_coasters || null;

      const starsVal = parseFloat(park.stars || 0);

      // Solo mostrar partes que no son null
      const infoParts = [
        country,
        year,
        num_coasters ? `${num_coasters} coasters` : null,
      ].filter(Boolean);
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
      card.href = BASE_URL + `/web/views/public/parks/parks.php?id=${park.id}`;
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
        <!-- Ranking e Imagen -->
        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-3 flex-shrink-0 mb-2 mb-md-0" style="min-width: 120px;">
          <div class="text-center" style="min-width:35px;">
            <span class="fw-black" style="font-size:1.3rem; color:${podiumColor};">#${position}</span>
          </div>
          <div class="flex-shrink-0">
            <img src="${validImgUrl || 'https://www.hussrides.com/fileadmin/_processed_/5/e/csm_giant-frisbee-cedarpoint-01_0697df513a.jpg'}" 
                 alt="${park.park_name}" 
                 class="rounded shadow-sm" 
                 referrerpolicy="no-referrer" 
                 style="width:75px; height:75px; object-fit:cover; border: 1px solid rgba(255,255,255,0.15);">
          </div>
        </div>

        <!-- Info Principal -->
        <div class="flex-grow-1 px-md-3 min-w-0 text-center text-md-start w-100">
          <h5 class="mb-1 fw-bold text-white text-truncate" style="font-family: var(--rcw-font-title); font-size: 1.1rem; letter-spacing: -0.01em;">${park.park_name}</h5>
          <p class="mb-1 text-muted small text-truncate"><i class="fa-solid fa-map-pin me-1 opacity-50"></i>${park.park_location}</p>
          ${infoLine ? `<small class="text-secondary opacity-75 d-block text-truncate">${infoLine}</small>` : ""}
        </div>

        <!-- Estrellas -->
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center justify-content-md-end gap-3 mt-2 mt-md-0" style="min-width: 90px;">
          ${starsVal > 0 ? `
            <div class="d-flex align-items-center gap-1 bg-dark bg-opacity-25 px-2 py-1 rounded">
              <span class="fw-bold text-warning" style="font-size:1.1rem;">${starsVal.toFixed(2)}</span>
              <i class="fa-solid fa-star text-warning" style="font-size:0.85rem;"></i>
            </div>
          ` : ""}
          <i class="fa-solid fa-chevron-right text-muted opacity-25 d-none d-md-block ms-1"></i>
        </div>
      `;
      parkList.appendChild(card);
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
      loadParks(page - 1);
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
      loadParks(1);
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
        loadParks(i);
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
      loadParks(totalPages);
    });
    pageBtn.appendChild(btnLast);

    const nextBtn = document.createElement("button");
    nextBtn.className = "btn btn-outline-success mx-1";
    nextBtn.textContent = "»";
    if (page === totalPages) nextBtn.disabled = true;
    nextBtn.addEventListener("click", () => {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadParks(page + 1);
    });
    pageBtn.appendChild(nextBtn);

    paginationEl.appendChild(pageBtn);
  }

  loadParks(1);
});
