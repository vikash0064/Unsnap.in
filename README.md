  # 📸 Unsnap – AI-Enhanced Image Upload and Sharing Platform

[Live Site 🌐](https://unsnap.ct.ws)

**Unsnap** is a feature-rich, secure, and responsive image uploading and sharing platform built using modern web technologies. Designed for scalability and simplicity, it offers seamless image uploads, real-time previews, user authentication (including Google OAuth), and image categorization with CSRF protection.

---

## 🚀 Features

- ✅ **User Authentication** (Email login + Google OAuth)
- 📤 **Secure Image Upload** with file validation & thumbnail creation
- 👤 **Profile Dashboard** to view, delete, and manage uploads
- 🏷️ **Category-based Sorting** (e.g., Nature, Travel, Art)
- 🔐 **CSRF Protection** and Session Management
- 📱 **Responsive Design** with Tailwind CSS
- 📸 **Image Viewer** with modal previews and action buttons
- 🧠 **AI-Ready Backend** (Gemini API or GPT-based tagging can be integrated)
- 🧩 **Modular Codebase** with separate concerns (auth, display, upload)

---

## 🧑‍💻 How to Use Unsnap

Follow these simple steps to start uploading and managing your images on **Unsnap**:

### 🟢 1. Visit the Website  
Go to the live site:  
👉 [https://unsnap.ct.ws](https://unsnap.ct.ws)

### 🔐 2. Sign Up or Log In

- 📨 Use your **email and password** to register or log in.
- 🔒 Session-based authentication keeps your uploads secure.
- *(Optional: If Google Login is enabled, use "Login with Google")*

### 📤 3. Upload Images

- Click on the **Upload** button.
- Choose an image from your device.
- Add optional **category** or **caption** (if available).
- Click **Submit** to upload the image.
- The system will:
  - Validate file type and size
  - Create a **thumbnail**
  - Store the image securely in the backend

### 🖼️ 4. View Uploaded Images

- Go to your **Saved Images** or **Profile** page
- All your uploaded images will be displayed with:
  - Thumbnail preview
  - Category
  - Upload date
  - Delete option

### 🗑️ 5. Manage Your Uploads

- Click the 🗑️ (Delete) icon to remove an image from your gallery.
- You can only delete images that **you uploaded**.

### 🔐 6. Security and Privacy

- All uploads are **user-specific** and **protected by CSRF tokens**
- Only authenticated users can upload, view, and delete their images
- Your session will expire after inactivity to enhance security

---

## 🧰 Tech Stack

| Layer        | Technology                                |
|--------------|-------------------------------------------|
| **Frontend** | HTML5, Tailwind CSS, JavaScript           |
| **Backend**  | PHP 8.x                                   |
| **Database** | MySQL                                     |
| **Hosting**  | Free PHP Hosting (`ct.ws` domain via InfinityFree) |
| **Auth**     | Sessions, Google OAuth via Firebase/Auth |
| **Security** | CSRF Tokens, Input Sanitization, MIME Validation |

---

## 🧱 Folder Structure
unsnap/
├── index.php              # Home page with login/register UI
├── upload.php             # Handles image upload logic
├── saved.php              # Displays uploaded images for logged-in user
├── logout.php             # Destroys session and logs out user
├── README.md              # Project documentation
├── LICENSE                # Open-source license (MIT)
├── .gitignore             # Prevents sensitive files from being pushed
│
├── includes/              # PHP includes (logic & backend utilities)
│   ├── db.php             # Database connection settings
│   ├── auth.php           # User session & login check
│   ├── csrf.php           # CSRF token generation/verification
│   └── functions.php      # Common helper functions (file name clean-up, etc.)
│
├── assets/                # All frontend styling & scripts
│   ├── css/
│   │   └── style.css      # Tailwind or custom CSS
│   ├── js/
│   │   └── script.js      # JS for modal, preview, interactivity
│   └── images/            # Static images like logos or icons
│
├── uploads/               # Uploaded image storage
│   ├── full/              # Original uploaded images
│   └── thumbnails/        # Auto-resized thumbnail previews


