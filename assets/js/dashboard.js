/*==========================================
TASKFLOW DASHBOARD
==========================================*/

document.addEventListener("DOMContentLoaded", function () {

    /* Sidebar */

    const sidebar = document.querySelector(".sidebar");
    const menuToggle = document.getElementById("menuToggle");

    if (menuToggle) {

        menuToggle.addEventListener("click", function () {

            sidebar.classList.toggle("show");

        });

    }

    /* Counter Animation */

    document.querySelectorAll(".stat-value").forEach(function (counter) {

        const target = parseInt(counter.innerText);

        if (isNaN(target)) return;

        let count = 0;

        const speed = Math.max(10, Math.floor(1500 / (target || 1)));

        counter.innerText = "0";

        const update = () => {

            count++;

            counter.innerText = count;

            if (count < target) {

                setTimeout(update, speed);

            }

        };

        update();

    });

    /* Search */

    const search = document.getElementById("dashboardSearch");

    if (search) {

        search.addEventListener("keyup", function () {

            const keyword = this.value.toLowerCase();

            document.querySelectorAll("table tbody tr").forEach(function (row) {

                row.style.display = row.innerText.toLowerCase().includes(keyword)
                    ? ""
                    : "none";

            });

        });

    }

    /* Card Animation */

    const cards = document.querySelectorAll(

        ".stat-card,.widget,.table-card,.card"

    );

    cards.forEach(function (card, index) {

        card.style.opacity = 0;

        card.style.transform = "translateY(25px)";

        setTimeout(function () {

            card.style.transition = ".45s";

            card.style.opacity = 1;

            card.style.transform = "translateY(0)";

        }, index * 120);

    });

    /* Dropdown */

    const profile = document.querySelector(".profile-dropdown");

    if (profile) {

        profile.addEventListener("click", function (e) {

            e.stopPropagation();

            const menu = profile.querySelector(".dropdown-menu");

            menu.style.display =

                menu.style.display === "block"

                    ? "none"

                    : "block";

        });

        document.addEventListener("click", function () {

            const menu = profile.querySelector(".dropdown-menu");

            menu.style.display = "none";

        });

    }

    /* Active Menu */

    document.querySelectorAll(".sidebar-menu a").forEach(function (link) {

        link.addEventListener("click", function () {

            document

                .querySelectorAll(".sidebar-menu a")

                .forEach(x => x.classList.remove("active"));

            this.classList.add("active");

        });

    });

    /* Chart */

    if (document.getElementById("taskChart")) {

        new Chart(

            document.getElementById("taskChart"),

            {

                type: "bar",

                data: {

                    labels: [

                        "Pending",

                        "In Progress",

                        "Completed",

                        "Overdue"

                    ],

                    datasets: [

                        {

                            data: [

                                window.dashboardData?.pending || 0,

                                window.dashboardData?.progress || 0,

                                window.dashboardData?.completed || 0,

                                window.dashboardData?.overdue || 0

                            ],

                            borderWidth: 0,

                            borderRadius: 12,

                            backgroundColor: [

                                "#f59e0b",

                                "#3b82f6",

                                "#22c55e",

                                "#ef4444"

                            ]

                        }

                    ]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {

                            display: false

                        }

                    },

                    scales: {

                        x: {

                            ticks: {

                                color: "#cbd5e1"

                            },

                            grid: {

                                display: false

                            }

                        },

                        y: {

                            beginAtZero: true,

                            ticks: {

                                color: "#cbd5e1"

                            },

                            grid: {

                                color: "rgba(255,255,255,.06)"

                            }

                        }

                    }

                }

            }

        );

    }

});