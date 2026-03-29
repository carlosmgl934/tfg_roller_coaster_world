$(document).ready(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('id');
    const profileContent = $("#profile-content");
    const loading = $("#profile-loading");

    if (!userId) {
        loading.html('<div class="alert alert-warning">No se ha especificado un ID de usuario.</div>');
        return;
    }

    fetchProfileData(userId);

    async function fetchProfileData(id) {
        try {
            const res = await fetch(`${BASE_URL}/api/php/users.php?action=get_public_profile&id=${id}`);
            const data = await res.json();

            if (data.success) {
                renderProfile(data.data);
                loading.hide();
                profileContent.fadeIn();
            } else {
                loading.html(`<div class="alert alert-danger">${data.error || "Perfil no disponible"}</div>`);
            }
        } catch (e) {
            console.error(e);
            loading.html('<div class="alert alert-danger">Error conectando con el servidor.</div>');
        }
    }

    function renderProfile(data) {
        const user = data.user;
        const stats = data.stats;
        const topParks = data.top_parks;
        const fStatus = data.friendship_status;

        // Header Info
        $("#user-username").text(user.username);
        const cityStr = user.city ? user.city : "";
        const countryStr = user.country ? user.country : "";
        const loc = (cityStr && countryStr) ? `${cityStr}, ${countryStr}` : (cityStr || countryStr || "Ubicación desconocida");
        $("#user-location span").text(loc);
        
        // Avatar
        const avatarDiv = $("#user-avatar");
        if (user.profile_image) {
            const imgSrc = user.profile_image.startsWith('/') ? BASE_URL + user.profile_image : user.profile_image;
            avatarDiv.html(`<img src="${imgSrc}" class="w-100 h-100 object-fit-cover rounded-circle shadow">`);
        } else {
            const initials = user.username.substring(0, 2).toUpperCase();
            avatarDiv.text(initials);
        }

        // Stats
        $("#stat-coasters").text(stats.coasters);
        $("#stat-parks").text(stats.parks);
        $("#user-fav-coaster").text(user.favorite_coaster || "Buscando su favorita...");
        $("#user-home-park").text(user.home_park || "Nómada de parques");
        
        const joinedDate = new Date(user.created_at).toLocaleDateString();
        $("#user-joined").text(joinedDate);

        // Friendship Button
        renderFriendshipButton(user.id, fStatus);

        // Top Parks
        renderTopParks(topParks);
    }

    function renderFriendshipButton(targetId, status) {
        const container = $("#friendship-action-container");
        container.empty();

        if (status === null) return; // Own profile or not logged in

        let btnHtml = "";
        if (status === 'none') {
            btnHtml = `<button class="btn btn-success fw-bold px-4 rounded-pill shadow-sm py-2 action-friend" data-action="request">
                        <i class="fa-solid fa-user-plus me-2"></i>Enviar Solicitud
                       </button>`;
        } else if (status === 'pending_sent') {
            btnHtml = `<button class="btn btn-outline-secondary fw-bold px-4 rounded-pill py-2 action-friend" data-action="cancel">
                        <i class="fa-solid fa-clock me-2"></i>Solicitud Enviada
                       </button>`;
        } else if (status === 'pending_received') {
            btnHtml = `<div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-success fw-bold px-3 rounded-pill py-2 action-friend" data-action="accept">
                            <i class="fa-solid fa-check me-1"></i>Aceptar
                        </button>
                        <button class="btn btn-outline-danger fw-bold px-3 rounded-pill py-2 action-friend" data-action="reject">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                       </div>`;
        } else if (status === 'accepted') {
            btnHtml = `<div class="dropdown">
                        <button class="btn btn-outline-success fw-bold px-4 rounded-pill py-2 dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-check me-2"></i>Amigos
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item text-danger action-friend" href="#" data-action="remove"><i class="fa-solid fa-user-minus me-2"></i>Eliminar Amigo</a></li>
                        </ul>
                       </div>`;
        }

        container.html(btnHtml);

        // Bind Action
        $(".action-friend").off("click").on("click", async function(e) {
            e.preventDefault();
            const btn = $(this);
            const action = btn.data("action");
            
            btn.prop("disabled", true).prepend('<span class="spinner-border spinner-border-sm me-2"></span>');

            let endpoint = "";
            let method = "POST";

            if (action === "request") endpoint = "friend_request";
            else if (action === "accept") endpoint = "accept_friend";
            else if (action === "reject" || action === "remove" || action === "cancel") endpoint = "reject_remove_friend";

            try {
                const res = await fetch(`${BASE_URL}/api/php/users.php?action=${endpoint}`, {
                    method: method,
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ target_id: targetId })
                });
                const resData = await res.json();
                if (resData.success) {
                    // Re-fetch everything to update UI
                    fetchProfileData(targetId);
                } else {
                    alert("Error: " + (resData.error || "No se pudo realizar la acción"));
                    fetchProfileData(targetId);
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    function renderTopParks(parks) {
        const container = $("#user-tops-container");
        container.empty();

        if (parks.length === 0) {
            container.html('<div class="col-12 text-center py-4 text-muted">Este aventurero aún no ha definido su Top 5 de parques.</div>');
            return;
        }

        parks.forEach(park => {
            const fallback = "https://cdn.hourdetroit.com/wp-content/uploads/sites/20/2019/05/Cedar-Point-Main-4.png";
            const img = park.imagen_url ? (park.imagen_url.startsWith('/') ? BASE_URL + park.imagen_url : park.imagen_url) : fallback;
            const link = `${BASE_URL}/web/views/public/parks/parks.php?id=${park.id}`;

            container.append(`
                <div class="col-12 col-md-6 col-xl-4 animate__animated animate__fadeIn">
                    <a href="${link}" class="text-decoration-none">
                        <div class="card bg-dark bg-opacity-50 text-white border-secondary border-opacity-25 h-100 hover-scale" style="transition: transform 0.2s;">
                            <div class="position-relative" style="height: 120px;">
                                <img src="${img}" class="w-100 h-100 object-fit-cover rounded-top">
                                <span class="position-absolute top-0 start-0 m-2 badge bg-success shadow">#${park.rank_position}</span>
                            </div>
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-0 text-truncate text-white">${park.park_name}</h6>
                                <small class="text-muted d-block text-truncate">${park.park_country}</small>
                            </div>
                        </div>
                    </a>
                </div>
            `);
        });
    }
});
