            A Comprehensive Web Portal for the ClubManagement Project            
================================================================================

[ 1 ] OVERVIEW
--------------------------------------------------------------------------------
CLUBManager is a dynamic, user-friendly web application designed to streamline
the administrative and interactive processes of a club or organization. It
provides a centralized platform for both members and administrators, simplifying
tasks such as member registration, event management, profile updates, and more.
This portal enhances operational efficiency and fosters a more connected and
engaged club community.

================================================================================

[ 2 ] KEY FEATURES
--------------------------------------------------------------------------------

   --- MEMBER FUNCTIONALITY ---

   •  Secure Registration & Login: Easy and secure onboarding for new members
      and authentication for existing ones.
   •  Personalized Dashboard: A central hub for members to view their profile,
      membership status, and upcoming event registrations.
   •  Profile Management: Members can effortlessly view and update their
      personal details, ensuring records are always current.
   •  Password Security: A secure interface for members to change their
      passwords.
   •  Event Discovery: Browse a comprehensive list of all upcoming and past
      club events.
   •  Effortless Event Registration: A simple, one-click process for members to
      sign up for events.
   •  Registration Tracking: View a personalized list of all events for which
      a member is currently registered.
   •  Membership Renewal: A straightforward portal for renewing memberships to
      maintain active status.

   --- ADMINISTRATIVE FUNCTIONALITY ---

   •  Central Admin Dashboard: An intuitive control panel providing a high-level
      overview and quick access to all management modules.
   •  Complete Member Management: Administrators can view, add, edit, and
      delete member profiles with ease.
   •  Full Event Control: Create, update, and manage all aspects of club events,
      from scheduling to details.
   •  Secure Admin Access: Dedicated and protected login for authorized
      administrators.

================================================================================

[ 3 ] TECHNICAL SPECIFICATIONS & PREREQUISITES
--------------------------------------------------------------------------------

To deploy and run this project, your environment must meet the following
requirements:

   •  Server Environment: Apache (recommended) or any other web server with
      PHP support.
   •  Database Server: MySQL or MariaDB.
   •  Programming Language: PHP version 7.4 or higher.
   •  Web Browser: Compatible with modern browsers such as Chrome, Firefox,
      Safari, and Edge.

================================================================================

[ 4 ] INSTALLATION & SETUP GUIDE
--------------------------------------------------------------------------------

Follow these steps to set up the project on your local server:

   1.  **Clone/Download the Project:**
       Place all project files into your web server's root directory (e.g.,
       `/htdocs` for XAMPP, `/var/www/html` for a standard LAMP stack).

   2.  **Database Configuration:**
       -  Launch your database management tool (e.g., phpMyAdmin).
       -  Create a new database and name it `clubmanager`.
       -  Select the `clubmanager` database and import the `clubmanager.sql`
          file provided in the project root. This will set up all the
          necessary tables and relationships.

   3.  **Connection Settings:**
       -  Navigate to the `Dashboard/` directory and open the `config.php` file.
       -  Modify the database credentials (DB_SERVER, DB_USERNAME, DB_PASSWORD,
          DB_NAME) to match your local database server setup.

   4.  **Admin Credentials:**
       -  The default administrator login credentials are hardcoded in the
         `backend/admin_login_process.php` file.
       -  Default Username: `admin`
       -  Default Password: `admin`
       -  It is highly recommended to change these credentials for any
         production environment.

   5.  **Launch the Application:**
       -  Open your web browser and navigate to the project's URL (e.g.,
         `http://localhost/CLUBManager/`).

================================================================================

[ 5 ] PROJECT FILE STRUCTURE
--------------------------------------------------------------------------------

    CLUBManager/
    ├── Dashboard/              # Core dashboard files
    │   ├── admin_dashboard.php # Admin's main view
    │   ├── config.php          # Database and timezone configuration
    │   └── main_board.php      # Member's main view
    ├── backend/                # Server-side processing logic
    │   ├── admin_login_process.php
    │   ├── db_connect.php
    │   ├── delete_member.php
    │   ├── login_process.php
    │   ├── logout.php
    │   ├── register_event.php
    │   ├── register_process.php
    │   ├── update_password.php
    │   └── update_profile.php
    ├── assets/                 # CSS, JavaScript, and other static assets
    │   ├── ajax.js
    │   ├── scripts.js
    │   ├── styles.css
    │   └── validation.js
    ├── images/                 # Site images and backgrounds
    ├── admin.html              # Admin login page
    ├── admin_manage_members.php # Page for admins to manage members
    ├── change_password.php     # Page for members to change password
    ├── clubmanager.sql         # The database schema
    ├── edit_profile.php        # Page for members to edit their profile
    ├── event_details.php       # Detailed view of a single event
    ├── event_list.php          # List of all club events
    ├── index.html              # Main landing/login selection page
    ├── my_registrations.php    # Member's list of registered events
    ├── register.php            # New member registration page
    └── student.html            # Member login page

================================================================================

[ 6 ] USAGE INSTRUCTIONS
--------------------------------------------------------------------------------

   --- As a Member ---
   1.  **Registration:** Navigate to the `register.php` page from the main index
       to create a new account.
   2.  **Login:** Use the "Student Login" button on the `index.html` page to
       access your account.
   3.  **Navigate:** Use the sidebar in your dashboard to access your profile,
       browse events, view your registrations, and change your password.

   --- As an Administrator ---
   1.  **Login:** Use the "Admin Login" button on the `index.html` page.
   2.  **Dashboard:** The admin dashboard provides quick actions and an overview
       of the club's status.
   3.  **Management:** Use the sidebar to navigate to the "Manage Members" and
       "Manage Events" sections to perform administrative tasks.

================================================================================
|                                                                                                                                   |
|                                Thank you for using CLUBManager!                                           |
|                                                                                                                                   |
================================================================================
