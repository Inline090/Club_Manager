<?php
// ALWAYS start session FIRST at the very top
session_start();

// Check if the user is logged in (can be student or admin, adjust if needed)
$is_logged_in = (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) ||
                (isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true);

if (!$is_logged_in) {
    // Redirect to student login if not logged in at all
    header("location: student.html?error=not_logged_in");
    exit;
}

// Determine user type for sidebar links (optional, adjust paths if needed)
$user_type = isset($_SESSION["admin_logged_in"]) ? 'admin' : 'student';
$dashboard_link = ($user_type === 'admin') ? 'Dashboard/admin_dashboard.php' : 'Dashboard/main_board.php';
$logout_link = ($user_type === 'admin') ? 'backend/admin_logout.php' : 'backend/logout.php';


// --- Include Club Data ---
// (You can put the $clubs array directly here, or include it from another file)
$clubs = [
    [
        'name' => 'DaVinci ART CLUB',
        'image' => 'images/clubs/art_club.jpg', // Paths relative to root now
        'description' => 'Unleash your inner artist! Explore painting, sketching, digital art, crafts, and more. No prior experience needed, just a passion for creativity. Join us for workshops, exhibitions, and collaborative projects.'
    ],
    [
        'name' => 'Elixr Finance Club',
        'image' => 'images/clubs/finance_club.jpg',
        'description' => 'Decode the world of finance. Dive into stock markets, investing strategies, economic trends, and personal finance management. Engage in discussions, simulations, and expert talks.'
    ],
    [
        'name' => 'Astronomy Club',
        'image' => 'images/clubs/astronomy_club.jpg',
        'description' => 'Gaze beyond our world! Join fellow stargazers to explore planets, distant galaxies, and cosmic wonders. Participate in observation nights, workshops, and discussions about the universe.'
    ],
    [
        'name' => 'Cyberspace Club',
        'image' => 'images/clubs/cyber_club.jpg',
        'description' => 'Navigate the digital frontier. Delve into cybersecurity essentials, ethical hacking, programming, networking fundamentals, and the latest emerging technologies. Learn, secure, innovate.'
    ],
    [
        'name' => 'Motioncraft Animation Club',
        'image' => 'images/clubs/animation_club.jpg',
        'description' => 'Bring your stories to life! Learn the principles of 2D and 3D animation, character design, visual storytelling, and industry-standard software. Collaborate on animated shorts and projects.'
    ],
    [
        'name' => 'Ignite Club',
        'image' => 'images/clubs/ignite_club.jpg',
        'description' => 'Spark innovation and ignite change! A hub for aspiring entrepreneurs and changemakers. Develop business ideas, learn startup fundamentals, network, and pitch your ventures.'
    ],
    [
        'name' => 'CodeX Club',
        'image' => 'images/clubs/codex_club.jpg',
        'description' => 'Crack the code! A community for passionate programmers. Sharpen your coding skills across various languages, tackle algorithmic challenges, contribute to open source, and build amazing software.'
    ],
    [
        'name' => 'Graphica Club',
        'image' => 'images/clubs/graphica_club.jpg',
        'description' => 'Design the future, visually. Master graphic design principles, branding, UI/UX design, typography, and digital illustration. Create compelling visuals for print and screen.'
    ],
    [
        'name' => 'Endurance Club',
        'image' => 'images/clubs/endurance_club.jpg',
        'description' => 'Push your limits, together. For students passionate about running, cycling, swimming, and other endurance sports. Join group training sessions, participate in events, and build physical and mental resilience.'
    ]
];

$total_clubs = count($clubs);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Clubs - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        /* Base styles */
        ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: #c5c5c5; border-radius: 3px; } ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        @import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }
        body { font-family: 'Inter', sans-serif; }

        /* Styles for the Club Display Area */
        #club-display-area {
            min-height: 450px; /* Ensure area has height */
            transition: opacity 0.4s ease-in-out; /* Transition for fade effect */
            opacity: 1; /* Start visible */
        }
        #club-display-area.loading {
            opacity: 0; /* Fade out when loading new content */
        }

        /* Ensure images fit well */
        #club-image {
            max-height: 300px; /* Limit image height */
            object-fit: cover; /* Cover the area nicely */
        }
         /* Style for disabled buttons */
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

    </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
        <!-- Sidebar (Optional - you can use the one from main_board or a simplified one) -->
        <aside class="w-64 bg-white shadow-md flex flex-col rounded-tr-3xl rounded-br-3xl overflow-hidden">
            <!-- Sidebar Header -->
            <div class="pt-4 pb-2 text-center border-b border-gray-200">
                <a href="<?php echo $dashboard_link; ?>" class="inline-block">
                    <img src="images/logo.jpg" alt="Club Logo" class="h-[60px] w-auto mx-auto rounded-lg">
                </a>
                <h1 class="text-xl font-bold text-indigo-600 mt-2">Club Portal</h1>
                <p class="text-sm text-gray-600 mt-1">Explore</p>
            </div>
            <!-- Navigation -->
             <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <!-- Simplified Nav for this page -->
                <a href="<?php echo $dashboard_link; ?>" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-arrow-left w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Back to Dashboard </a>
                 <a href="explore_clubs.php" class="flex items-center px-4 py-2.5 text-gray-700 bg-indigo-50 text-indigo-600 font-semibold rounded-lg group"> <i class="fa-solid fa-puzzle-piece w-6 h-6 mr-3 text-indigo-500"></i> Explore Clubs </a>
                 <a href="event_list.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-calendar-check w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Events </a>

             </nav>
            <!-- Logout -->
            <div class="p-4 border-t border-gray-200 mt-auto">
                <a href="<?php echo $logout_link; ?>" class="flex items-center text-sm text-gray-500 hover:text-indigo-600"> <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-2"></i> Logout </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 p-8 overflow-y-auto flex flex-col items-center justify-center">
            <h2 class="text-4xl font-bold text-gray-800 mb-10 text-center">Explore Our Clubs</h2>

            <div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-3xl mx-auto relative">

                <!-- Dynamic Club Display Area -->
                <div id="club-display-area" class="text-center">
                    <img id="club-image" src="<?php echo htmlspecialchars($clubs[0]['image']); ?>" alt="Club Image" class="w-full rounded-lg mb-6 shadow">
                    <h3 id="club-name" class="text-3xl font-semibold text-indigo-700 mb-4"><?php echo htmlspecialchars($clubs[0]['name']); ?></h3>
                    <p id="club-description" class="text-gray-600 leading-relaxed mb-8"><?php echo htmlspecialchars($clubs[0]['description']); ?></p>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between items-center mt-6">
                     <button id="prev-club" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-6 rounded-lg transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                         <i class="fas fa-arrow-left mr-2"></i> Previous
                     </button>
                     <span id="club-counter" class="text-sm text-gray-500 font-medium">1 / <?php echo $total_clubs; ?></span>
                     <button id="next-club" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-6 rounded-lg transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                         Next <i class="fas fa-arrow-right ml-2"></i>
                     </button>
                </div>
            </div>
        </main>
</div>

<script>
    // Get DOM elements
    const displayArea = document.getElementById('club-display-area');
    const clubImage = document.getElementById('club-image');
    const clubName = document.getElementById('club-name');
    const clubDescription = document.getElementById('club-description');
    const prevButton = document.getElementById('prev-club');
    const nextButton = document.getElementById('next-club');
    const clubCounter = document.getElementById('club-counter');

    // Club data from PHP (ensure correct encoding)
    const clubs = <?php echo json_encode($clubs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
    const totalClubs = clubs.length;
    let currentClubIndex = 0;

    // Function to update the display
    function displayClub(index) {
        if (index < 0 || index >= totalClubs) {
            console.error("Invalid club index:", index);
            return; // Prevent invalid index
        }

        const club = clubs[index];

        // Add loading class to start fade out
        displayArea.classList.add('loading');

        // Wait for fade out transition to complete before updating content
        setTimeout(() => {
            clubName.textContent = club.name;
            clubDescription.textContent = club.description;
            clubImage.src = club.image; // Update image source
            clubImage.alt = club.name + " Image"; // Update alt text
            clubCounter.textContent = (index + 1) + ' / ' + totalClubs;

            // Update button states
            prevButton.disabled = (index === 0);
            nextButton.disabled = (index === totalClubs - 1);

             // Remove loading class to fade back in
            displayArea.classList.remove('loading');

        }, 400); // Match this duration (in ms) to the CSS transition duration
    }

    // Event Listeners for buttons
    prevButton.addEventListener('click', () => {
        if (currentClubIndex > 0) {
            currentClubIndex--;
            displayClub(currentClubIndex);
        }
    });

    nextButton.addEventListener('click', () => {
        if (currentClubIndex < totalClubs - 1) {
            currentClubIndex++;
            displayClub(currentClubIndex);
        }
    });

    // Initial display
    displayClub(currentClubIndex); // Show the first club initially

</script>

</body>
</html>