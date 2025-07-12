const accessKey = "sFN2ciKbK12eYc1gNzSh2eyNckczjOInXFzu_4grsv4";
const searchForm = document.getElementById("search-form");
const searchBox = document.getElementById("search-box");
const searchResult = document.getElementById("search-result");
const showMoreBtn = document.getElementById("show-more-btn");
const tags = document.querySelectorAll(".tags span");

// Modal elements
const modal = document.getElementById("image-modal");
const modalImg = document.getElementById("modal-image");
const modalCreatorImg = document.getElementById("modal-creator-img");
const modalCreatorName = document.getElementById("modal-creator-name");
const modalDescription = document.getElementById("modal-description");
const modalDownloadBtn = document.getElementById("modal-download-btn");
const modalSaveBtn = document.getElementById("modal-save-btn");
const closeModal = document.querySelector(".close-modal");

// Mobile menu elements
const mobileMenu = document.getElementById('mobile-menu');
const navLinks = document.querySelector('.nav-links');

let keyword = "";
let page = 1;
let currentImageData = null;

// Mobile menu toggle
mobileMenu.addEventListener('click', () => {
    mobileMenu.classList.toggle('active');
    navLinks.classList.toggle('active');
});

// Close menu when clicking on a link
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        mobileMenu.classList.remove('active');
        navLinks.classList.remove('active');
    });
});

// Load popular images on page load
window.addEventListener('load', () => {
    keyword = "nature";
    searchImages();
});

// Form submission handler
searchForm.addEventListener("submit", (e) => {
    e.preventDefault();
    page = 1;
    keyword = searchBox.value.trim();
    if (keyword) {
        searchImages();
    } else {
        keyword = "nature";
        searchImages();
    }
});

async function searchImages() {
    keyword = keyword || "nature"; // Default to nature if empty
    const url = `https://api.unsplash.com/search/photos?page=${page}&query=${keyword}&client_id=${accessKey}&per_page=12`;

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (page === 1) {
            searchResult.innerHTML = "";
        }

        const results = data.results;

        if (results.length === 0) {
            searchResult.innerHTML = `<p class="no-results">No results found for "${keyword}". Try another search.</p>`;
            showMoreBtn.style.display = "none";
            return;
        }

        results.forEach((result) => {
            const imageCard = document.createElement("div");
            imageCard.className = "image-card";

            const image = document.createElement("img");
            image.src = result.urls.regular; // Load image immediately
            image.alt = result.alt_description || "Unsplash image";
            image.id = result.id;

            image.addEventListener("click", () => {
                openModal(result);
            });

            const imageActions = document.createElement("div");
            imageActions.className = "image-actions";

            const downloadBtn = document.createElement("button");
            downloadBtn.className = "download-btn";
            downloadBtn.title = "Download";
            downloadBtn.innerHTML = '<i class="fas fa-download"></i>';
            downloadBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                downloadImage(result.urls.full, `${keyword}-${result.id}.jpg`);
            });

            const saveBtn = document.createElement("button");
            saveBtn.className = "save-btn";
            saveBtn.title = "Save";
            saveBtn.innerHTML = '<i class="far fa-heart"></i>'; // default unsaved

            saveBtn.addEventListener("click", async (e) => {
                e.stopPropagation();
                const isSaved = await saveImage(result);
                if (isSaved) {
                    saveBtn.innerHTML = '<i class="fas fa-heart"></i>';
                    saveBtn.classList.add("saved");
                } else {
                    saveBtn.innerHTML = '<i class="far fa-heart"></i>';
                    saveBtn.classList.remove("saved");
                }
            });

            imageActions.appendChild(downloadBtn);
            imageActions.appendChild(saveBtn);

            const creatorInfo = document.createElement("div");
            creatorInfo.className = "creator-info";
            creatorInfo.innerHTML = `
                <img src="${result.user.profile_image.small}" alt="${result.user.name}">
                <span>${result.user.name}</span>
            `;

            imageCard.appendChild(image);
            imageCard.appendChild(imageActions);
            imageCard.appendChild(creatorInfo);
            searchResult.appendChild(imageCard);
        });

        showMoreBtn.style.display = "block";
    } catch (error) {
        console.error("Error fetching images:", error);
        searchResult.innerHTML = `<p class="error-message">Failed to load images. Please try again later.</p>`;
        showMoreBtn.style.display = "none";
    }
}

// Open modal with image details
function openModal(imageData) {
    currentImageData = imageData;
    modalImg.src = imageData.urls.regular;
    modalCreatorImg.src = imageData.user.profile_image.medium;
    modalCreatorName.textContent = imageData.user.name;
    modalDescription.textContent = imageData.alt_description || "No description available";
    modal.style.display = "block";
    document.body.style.overflow = "hidden";

    // Update save button data attributes
    modalSaveBtn.setAttribute('data-image-url', imageData.urls.full);
    modalSaveBtn.setAttribute('data-creator-name', imageData.user.name);
    modalSaveBtn.setAttribute('data-description', imageData.alt_description || 'No description');
}

// Check if image is saved in database
async function checkSavedStatus(imageId) {
    try {
        const response = await fetch(`check-saved.php?image_id=${imageId}`);
        const data = await response.json();
        return data.isSaved;
    } catch (error) {
        console.error("Error checking saved status:", error);
        return false;
    }
}

// Close modal
closeModal.addEventListener("click", () => {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
});

// Close modal when clicking outside
window.addEventListener("click", (e) => {
    if (e.target === modal) {
        modal.style.display = "none";
        document.body.style.overflow = "auto";
    }
});

// Modal download button
modalDownloadBtn.addEventListener("click", () => {
    if (currentImageData) {
        downloadImage(currentImageData.urls.full, `${keyword}-${currentImageData.id}.jpg`);
    }
});

// Modal save button
modalSaveBtn.addEventListener("click", async () => {
    if (currentImageData) {
        const isSaved = await saveImage(currentImageData);
        
        if (isSaved) {
            modalSaveBtn.innerHTML = '<i class="fas fa-heart"></i> Saved';
            modalSaveBtn.classList.add("saved");
        } else {
            modalSaveBtn.innerHTML = '<i class="far fa-heart"></i> Save';
            modalSaveBtn.classList.remove("saved");
        }
    }
});

// Unified save image function
async function saveImage(imageData) {
    try {
        // First check if user is logged in
        const loginCheck = await fetch('check-login.php');
        const loginData = await loginCheck.json();
        
        if (!loginData.loggedIn) {
            window.location.href = 'login.php';
            return false;
        }

        // Prepare form data
        const formData = new FormData();
        formData.append('image_url', imageData.urls.full);
        formData.append('creator_name', imageData.user.name);
        formData.append('description', imageData.alt_description || 'No description');

        // Send save request
        const response = await fetch('save.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            return true;
        } else {
            return false;
        }
    } catch (error) {
        console.error("Error saving image:", error);
        return false;
    }
}

// Modal save button handler
document.getElementById('modal-save-btn').addEventListener('click', async function() {
    if (!currentImageData) return;
    
    const btn = this;
    const originalHTML = btn.innerHTML;
    
    // Show loading state
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    const isSaved = await saveImage(currentImageData);
    
    // Update button state
    if (isSaved) {
        btn.innerHTML = '<i class="fas fa-heart"></i> Saved';
        btn.classList.add('saved');
    } else {
        btn.innerHTML = originalHTML;
        btn.classList.remove('saved');
        btn.disabled = false;
    }
});

// Theme Toggle Functionality (robust for live and localhost)
document.addEventListener('DOMContentLoaded', function() {
  const themeToggle = document.getElementById('theme-toggle-checkbox');
  if (!themeToggle) return;

  const currentTheme = localStorage.getItem('theme');
  if (currentTheme) {
    document.documentElement.setAttribute('data-theme', currentTheme);
    if (currentTheme === 'dark') themeToggle.checked = true;
  } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.setAttribute('data-theme', 'dark');
    themeToggle.checked = true;
  }

  themeToggle.addEventListener('change', function() {
    if (this.checked) {
      document.documentElement.setAttribute('data-theme', 'dark');
      localStorage.setItem('theme', 'dark');
    } else {
      document.documentElement.setAttribute('data-theme', 'light');
      localStorage.setItem('theme', 'light');
    }
  });

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    const newColorScheme = e.matches ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', newColorScheme);
    themeToggle.checked = newColorScheme === 'dark';
    localStorage.setItem('theme', newColorScheme);
  });
});

// Download image function
async function downloadImage(url, filename) {
    try {
        // Fetch the image
        const response = await fetch(url);
        
        // Check if request was successful
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Convert to blob
        const blob = await response.blob();
        const blobUrl = window.URL.createObjectURL(blob);
        
        // Create temporary anchor element
        const a = document.createElement("a");
        a.href = blobUrl;
        a.download = filename || `image-${Date.now()}.jpg`;
        a.style.display = "none";
        
        // Trigger download
        document.body.appendChild(a);
        a.click();
        
        // Clean up
        setTimeout(() => {
            document.body.removeChild(a);
            window.URL.revokeObjectURL(blobUrl);
        }, 100);
        
    } catch (error) {
        console.error("Download failed:", error);
    }
}

// Show more images
showMoreBtn.addEventListener("click", () => {
    page++;
    searchImages();
});

// Tag click handlers
tags.forEach(tag => {
    tag.addEventListener("click", () => {
        keyword = tag.textContent;
        page = 1;
        searchImages();
        searchBox.value = "";
    });
});

// Keyboard navigation in modal
document.addEventListener("keydown", (e) => {
    if (modal.style.display === "block") {
        if (e.key === "Escape") {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        }
    }
});

const maxPages = 6; // 6 pages (6 * 12 = 72 images)
let autoLoaded = false;

window.addEventListener("scroll", () => {
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;

    // When scrolled half page, auto-load up to 6 pages (if not already loaded)
    if (
        scrollTop + clientHeight >= scrollHeight / 2 &&
        showMoreBtn.style.display === "block" &&
        page < maxPages &&
        !autoLoaded
    ) {
        autoLoaded = true;
        let loadCount = 0;
        function loadNextPage() {
            if (page < maxPages) {
                page++;
                searchImages().then(() => {
                    loadCount++;
                    if (page < maxPages) {
                        loadNextPage();
                    } else {
                        showMoreBtn.style.display = "block";
                    }
                });
            }
        }
        loadNextPage();
    }

    // If max page reached, stop auto-loading
    if (page >= maxPages) {
        showMoreBtn.style.display = "block";
    }
});

// Function to check if an element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Function to check login status
async function checkLoginStatus() {
    try {
        const response = await fetch('check-login.php');
        const data = await response.json();
        
        if (data.loggedIn) {
            // Update UI for logged in user
            const loginBtn = document.querySelector('#login-btn');
            if (loginBtn) {
                loginBtn.innerHTML = '<i class="fas fa-user"></i> Profile';
                loginBtn.href = 'profile.php';
            }
        }
    } catch (error) {
        console.error("Error checking login status:", error);
    }
}

// Function to handle offline state
function handleConnection() {
    if (navigator.onLine) {
        console.log("Online");
    } else {
        console.log("Offline");
        searchResult.innerHTML = `
            <div class="offline-message">
                <i class="fas fa-wifi"></i>
                <h3>You're offline</h3>
                <p>Please check your internet connection and try again.</p>
            </div>
        `;
        showMoreBtn.style.display = "none";
    }
}

// Listen for online/offline events
window.addEventListener("online", handleConnection);
window.addEventListener("offline", handleConnection);

// Initial check
handleConnection();


const imageCard = document.createElement("div");
imageCard.classList.add("image-card");

const img = document.createElement("img");
img.src = image.urls.regular;
img.alt = image.alt_description;

imageCard.appendChild(img);
searchResult.appendChild(imageCard);
