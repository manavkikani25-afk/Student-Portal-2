/**
 * CampusSync - Interactive Client-Side JavaScript
 * Subject: Web Development Frameworks (ITUE203) - Practical 4, 5, 6
 */

document.addEventListener("DOMContentLoaded", () => {
    // -------------------------------------------------------------
    // 1. Light / Dark Theme Switcher with localStorage (Practical 4)
    // -------------------------------------------------------------
    const themeToggleBtn = document.getElementById("themeToggle");
    const currentTheme = localStorage.getItem("campussync_theme") || "light";

    if (currentTheme === "dark") {
        document.body.classList.add("dark-theme");
        if (themeToggleBtn) themeToggleBtn.innerHTML = "&#9788;"; // Sun
    } else {
        if (themeToggleBtn) themeToggleBtn.innerHTML = "&#9790;"; // Moon
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener("click", () => {
            const isDark = document.body.classList.toggle("dark-theme");
            localStorage.setItem("campussync_theme", isDark ? "dark" : "light");
            themeToggleBtn.innerHTML = isDark ? "&#9788;" : "&#9790;";
        });
    }

    // -------------------------------------------------------------
    // 2. Mobile Hamburger Menu Toggle (Practical 4)
    // -------------------------------------------------------------
    const menuButton = document.getElementById("menuButton");
    const navbar = document.getElementById("navbar");

    if (menuButton && navbar) {
        menuButton.addEventListener("click", () => {
            navbar.classList.toggle("show");
        });

        // Close navbar on outside click
        document.addEventListener("click", (e) => {
            if (!navbar.contains(e.target) && !menuButton.contains(e.target)) {
                navbar.classList.remove("show");
            }
        });
    }

    // -------------------------------------------------------------
    // 3. Notification Announcement Banner Dismissal (Practical 4)
    // -------------------------------------------------------------
    const banner = document.getElementById("notificationBanner");
    const closeBannerBtn = document.getElementById("closeBanner");

    if (closeBannerBtn && banner) {
        closeBannerBtn.addEventListener("click", () => {
            banner.style.display = "none";
            sessionStorage.setItem("campussync_banner_dismissed", "true");
        });

        if (sessionStorage.getItem("campussync_banner_dismissed") === "true") {
            banner.style.display = "none";
        }
    }

    // -------------------------------------------------------------
    // 4. Content / Image Carousel Slider (Practical 4)
    // -------------------------------------------------------------
    const sliderTrack = document.getElementById("sliderTrack");
    const slides = document.querySelectorAll(".slide");
    const prevBtn = document.getElementById("sliderPrev");
    const nextBtn = document.getElementById("sliderNext");
    const dotsContainer = document.getElementById("sliderDots");

    if (sliderTrack && slides.length > 0) {
        let currentSlide = 0;
        let slideInterval;

        // Create navigation dots
        if (dotsContainer) {
            dotsContainer.innerHTML = "";
            slides.forEach((_, idx) => {
                const dot = document.createElement("span");
                dot.classList.add("dot");
                if (idx === 0) dot.classList.add("active");
                dot.addEventListener("click", () => goToSlide(idx));
                dotsContainer.appendChild(dot);
            });
        }

        const updateDots = () => {
            const dots = document.querySelectorAll(".dot");
            dots.forEach((dot, idx) => {
                dot.classList.toggle("active", idx === currentSlide);
            });
        };

        const goToSlide = (index) => {
            currentSlide = (index + slides.length) % slides.length;
            sliderTrack.style.transform = `translateX(-${currentSlide * 100}%)`;
            updateDots();
        };

        const nextSlide = () => goToSlide(currentSlide + 1);
        const prevSlide = () => goToSlide(currentSlide - 1);

        if (nextBtn) nextBtn.addEventListener("click", () => { nextSlide(); resetAutoplay(); });
        if (prevBtn) prevBtn.addEventListener("click", () => { prevSlide(); resetAutoplay(); });

        const startAutoplay = () => {
            slideInterval = setInterval(nextSlide, 5000);
        };

        const resetAutoplay = () => {
            clearInterval(slideInterval);
            startAutoplay();
        };

        startAutoplay();
    }

    // -------------------------------------------------------------
    // 5. Collapsible Accordion (Practical 4 & FAQ)
    // -------------------------------------------------------------
    const accordionHeaders = document.querySelectorAll(".accordion-header");

    accordionHeaders.forEach(header => {
        header.addEventListener("click", () => {
            const parentItem = header.parentElement;
            const isOpen = parentItem.classList.contains("active");

            // Close other accordions in the same group
            document.querySelectorAll(".accordion-item").forEach(item => {
                item.classList.remove("active");
            });

            if (!isOpen) {
                parentItem.classList.add("active");
            }
        });
    });

    // -------------------------------------------------------------
    // 6. Modal Popup Notification (Practical 4)
    // -------------------------------------------------------------
    const modal = document.getElementById("campusNoticeModal");
    const openModalBtn = document.getElementById("openNoticeModal");
    const closeModalBtn = document.getElementById("closeNoticeModal");

    if (modal) {
        const showModal = () => modal.classList.add("show");
        const hideModal = () => modal.classList.remove("show");

        if (openModalBtn) openModalBtn.addEventListener("click", showModal);
        if (closeModalBtn) closeModalBtn.addEventListener("click", hideModal);

        modal.addEventListener("click", (e) => {
            if (e.target === modal) hideModal();
        });

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && modal.classList.contains("show")) {
                hideModal();
            }
        });
    }

    // -------------------------------------------------------------
    // 7. Student Registration Form Validation (Practical 5)
    // -------------------------------------------------------------
    const signupForm = document.getElementById("registrationForm");

    if (signupForm) {
        const nameInput = document.getElementById("full_name");
        const emailInput = document.getElementById("email");
        const mobileInput = document.getElementById("mobile");
        const pwdInput = document.getElementById("password");
        const confirmPwdInput = document.getElementById("confirm_password");
        const courseSelect = document.getElementById("course");
        const termsCheckbox = document.getElementById("terms");

        const strengthFill = document.getElementById("strengthFill");
        const strengthText = document.getElementById("strengthText");

        // Regex patterns
        const nameRegex = /^[A-Za-z\s]{3,50}$/;
        const emailRegex = /^[\w.-]+@[\w.-]+\.[A-Za-z]{2,}$/;
        const mobileRegex = /^[6-9]\d{9}$/;

        const setFieldError = (input, message) => {
            const formGroup = input.closest(".form-group");
            if (formGroup) {
                formGroup.classList.add("has-error");
                let errSpan = formGroup.querySelector(".error-message");
                if (errSpan) errSpan.textContent = message;
            }
        };

        const clearFieldError = (input) => {
            const formGroup = input.closest(".form-group");
            if (formGroup) {
                formGroup.classList.remove("has-error");
            }
        };

        // Real-time Name Validation
        if (nameInput) {
            nameInput.addEventListener("input", () => {
                if (!nameRegex.test(nameInput.value.trim())) {
                    setFieldError(nameInput, "Enter valid full name (letters and spaces only, 3-50 chars).");
                } else {
                    clearFieldError(nameInput);
                }
            });
        }

        // Real-time Email Validation
        if (emailInput) {
            emailInput.addEventListener("input", () => {
                if (!emailRegex.test(emailInput.value.trim())) {
                    setFieldError(emailInput, "Please enter a valid email address (e.g. name@domain.com).");
                } else {
                    clearFieldError(emailInput);
                }
            });
        }

        // Real-time Mobile Validation
        if (mobileInput) {
            mobileInput.addEventListener("input", () => {
                if (!mobileRegex.test(mobileInput.value.trim())) {
                    setFieldError(mobileInput, "Mobile number must be exactly 10 digits starting with 6-9.");
                } else {
                    clearFieldError(mobileInput);
                }
            });
        }

        // Password Strength Meter (Practical 5 requirement)
        if (pwdInput && strengthFill && strengthText) {
            pwdInput.addEventListener("input", () => {
                const val = pwdInput.value;
                let score = 0;

                if (val.length >= 8) score++;
                if (/[a-z]/.test(val)) score++;
                if (/[A-Z]/.test(val)) score++;
                if (/[0-9]/.test(val)) score++;
                if (/[^A-Za-z0-9]/.test(val)) score++;

                if (val.length === 0) {
                    strengthFill.style.width = "0%";
                    strengthFill.style.backgroundColor = "transparent";
                    strengthText.textContent = "Enter password";
                    strengthText.style.color = "var(--text-muted)";
                } else if (score <= 2) {
                    strengthFill.style.width = "30%";
                    strengthFill.style.backgroundColor = "#ef4444"; // red
                    strengthText.textContent = "Weak (Include uppercase, numbers & symbols)";
                    strengthText.style.color = "#ef4444";
                } else if (score === 3 || score === 4) {
                    strengthFill.style.width = "70%";
                    strengthFill.style.backgroundColor = "#f59e0b"; // yellow
                    strengthText.textContent = "Medium Strength";
                    strengthText.style.color = "#f59e0b";
                } else {
                    strengthFill.style.width = "100%";
                    strengthFill.style.backgroundColor = "#10b981"; // green
                    strengthText.textContent = "Strong Password ✓";
                    strengthText.style.color = "#10b981";
                }
            });
        }

        // Confirm Password Match Check
        if (confirmPwdInput) {
            confirmPwdInput.addEventListener("input", () => {
                if (confirmPwdInput.value !== pwdInput.value) {
                    setFieldError(confirmPwdInput, "Passwords do not match.");
                } else {
                    clearFieldError(confirmPwdInput);
                }
            });
        }

        // Form Submit Handler
        signupForm.addEventListener("submit", (e) => {
            let isValid = true;

            if (!nameRegex.test(nameInput.value.trim())) {
                setFieldError(nameInput, "Please enter a valid full name.");
                isValid = false;
            }

            if (!emailRegex.test(emailInput.value.trim())) {
                setFieldError(emailInput, "Please enter a valid email address.");
                isValid = false;
            }

            if (!mobileRegex.test(mobileInput.value.trim())) {
                setFieldError(mobileInput, "Please enter a valid 10-digit mobile number.");
                isValid = false;
            }

            if (pwdInput.value.length < 8) {
                setFieldError(pwdInput, "Password must be at least 8 characters.");
                isValid = false;
            }

            if (confirmPwdInput.value !== pwdInput.value) {
                setFieldError(confirmPwdInput, "Password and confirm password must match.");
                isValid = false;
            }

            if (!courseSelect.value) {
                setFieldError(courseSelect, "Please choose your enrolled course.");
                isValid = false;
            }

            if (termsCheckbox && !termsCheckbox.checked) {
                alert("You must agree to the Terms of Service and Privacy Policy.");
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // -------------------------------------------------------------
    // 8. Fetch API External JSON Data Rendering (Practical 6)
    // -------------------------------------------------------------
    const eventsContainer = document.getElementById("eventsJsonList");
    const eventSearchInput = document.getElementById("eventSearch");
    const eventCategoryFilter = document.getElementById("eventCategoryFilter");
    const eventSortSelect = document.getElementById("eventSort");
    const paginationContainer = document.getElementById("eventPagination");

    if (eventsContainer) {
        let allEvents = [];
        let filteredEvents = [];
        let currentPage = 1;
        const itemsPerPage = 4;

        // Fetch JSON data
        fetch("events.json")
            .then(res => {
                if (!res.ok) throw new Error("Network response was not ok");
                return res.json();
            })
            .then(data => {
                allEvents = data;
                applyFilters();
            })
            .catch(err => {
                console.error("Fetch API Error:", err);
                eventsContainer.innerHTML = `
                    <div class="card text-center" style="grid-column: 1 / -1; padding: 40px;">
                        <p style="color: var(--danger);">Unable to load external events.json dataset.</p>
                        <small>Check browser console or ensure events.json exists.</small>
                    </div>`;
            });

        const renderEvents = () => {
            eventsContainer.innerHTML = "";

            if (filteredEvents.length === 0) {
                eventsContainer.innerHTML = `
                    <div class="card text-center" style="grid-column: 1 / -1; padding: 50px;">
                        <h3>No Events Found</h3>
                        <p class="text-muted">Try adjusting your search query or category filter.</p>
                    </div>`;
                if (paginationContainer) paginationContainer.innerHTML = "";
                return;
            }

            // Pagination slice
            const startIndex = (currentPage - 1) * itemsPerPage;
            const paginatedItems = filteredEvents.slice(startIndex, startIndex + itemsPerPage);

            paginatedItems.forEach(item => {
                const card = document.createElement("article");
                card.className = "card event-card";
                card.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 12px;">
                        <span class="badge badge-category">${item.category}</span>
                        ${item.badge ? `<span class="badge badge-upcoming">${item.badge}</span>` : ""}
                    </div>
                    <h3 style="font-size:1.2rem; margin-bottom: 10px;">${item.title}</h3>
                    <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 16px;">${item.description}</p>
                    <div style="font-size: 0.85rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: auto;">
                        <p>&#128197; <strong>Date:</strong> ${item.date} at ${item.time}</p>
                        <p>&#127979; <strong>Venue:</strong> ${item.venue}</p>
                        <p>&#128100; <strong>Organizer:</strong> ${item.organizer}</p>
                    </div>
                `;
                eventsContainer.appendChild(card);
            });

            renderPagination();
        };

        const renderPagination = () => {
            if (!paginationContainer) return;
            paginationContainer.innerHTML = "";

            const totalPages = Math.ceil(filteredEvents.length / itemsPerPage);
            if (totalPages <= 1) return;

            // Prev Button
            const prev = document.createElement("button");
            prev.className = "btn btn-secondary btn-sm";
            prev.textContent = "« Prev";
            prev.disabled = currentPage === 1;
            prev.addEventListener("click", () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderEvents();
                }
            });
            paginationContainer.appendChild(prev);

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                const pageBtn = document.createElement("button");
                pageBtn.className = `btn btn-sm ${i === currentPage ? "btn-primary" : "btn-secondary"}`;
                pageBtn.textContent = i;
                pageBtn.addEventListener("click", () => {
                    currentPage = i;
                    renderEvents();
                });
                paginationContainer.appendChild(pageBtn);
            }

            // Next Button
            const next = document.createElement("button");
            next.className = "btn btn-secondary btn-sm";
            next.textContent = "Next »";
            next.disabled = currentPage === totalPages;
            next.addEventListener("click", () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderEvents();
                }
            });
            paginationContainer.appendChild(next);
        };

        const applyFilters = () => {
            const query = (eventSearchInput ? eventSearchInput.value : "").toLowerCase().trim();
            const category = eventCategoryFilter ? eventCategoryFilter.value : "";
            const sortMode = eventSortSelect ? eventSortSelect.value : "date-asc";

            filteredEvents = allEvents.filter(ev => {
                const matchesQuery = ev.title.toLowerCase().includes(query) ||
                                     ev.description.toLowerCase().includes(query) ||
                                     ev.organizer.toLowerCase().includes(query) ||
                                     ev.venue.toLowerCase().includes(query);
                const matchesCategory = category === "" || ev.category === category;
                return matchesQuery && matchesCategory;
            });

            // Sorting
            filteredEvents.sort((a, b) => {
                if (sortMode === "date-asc") return new Date(a.date) - new Date(b.date);
                if (sortMode === "date-desc") return new Date(b.date) - new Date(a.date);
                if (sortMode === "title-asc") return a.title.localeCompare(b.title);
                return 0;
            });

            currentPage = 1;
            renderEvents();
        };

        if (eventSearchInput) eventSearchInput.addEventListener("input", applyFilters);
        if (eventCategoryFilter) eventCategoryFilter.addEventListener("change", applyFilters);
        if (eventSortSelect) eventSortSelect.addEventListener("change", applyFilters);
    }
});
