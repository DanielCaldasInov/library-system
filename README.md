<p>
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

<h1>Library Management System</h1>

<p>
A library management system built with Laravel, Jetstream and Inertia.js
</p>

---

## 📚 About the Project

This project is a **Library Management System** developed as part of my internship experience.  
Its goal is to manage **books, authors and publishers** in a structured, scalable and modern way, following best practices of the Laravel ecosystem.

The application uses **server-side rendering with Inertia.js**, allowing Laravel to handle routing, validation and business logic while Vue.js is responsible for the user interface.

---

## 🚀 Current Features

The project is under active development and currently includes:

- Authentication and user management using **Laravel Jetstream**
- Book, Author and Publisher management
- Reusable and generic **index pages** with:
    - Search and filtering
    - Sortable table columns
    - Pagination
- Eager loading to optimize database queries
- Clean separation of concerns using:
    - Controllers
    - Reusable Vue components
- Responsive UI using **Tailwind CSS** and **DaisyUI**
- Optimized export of data to Excel (using Laravel Excel)
- Prepared structure for data encryption when needed

---

## 🛠️ Technologies Used

- **Laravel** – Backend framework
- **Jetstream** – Authentication scaffolding
- **Inertia.js** – Bridge between backend and frontend
- **Vue.js (Vue 3)** – Frontend framework
- **MySQL** – Database
- **Tailwind CSS + DaisyUI** – Styling
- **Laravel Excel** – Data export

---

## 📂 Project Structure Highlights

- `Controllers` handle business logic and data queries
- `Vue Components` are reused across Books, Authors and Publishers
- Index pages share common components:
    - SearchForm
    - DataTable
    - Pagination
- Queries are optimized with eager loading and conditional filters

This structure makes the project easy to extend with new entities and features.

---

## 🔐 Security & Data Handling

- Authentication handled by Laravel Jetstream
- Sensitive data can be encrypted using Laravel’s native encryption features
- The application follows Laravel security best practices

---

## 👨‍💻 About Me

**Daniel Silva**  
Software Development Intern

I am a developer focused on building clean, maintainable and scalable applications.  
This project represents my learning process and practical experience with Laravel, modern frontend tooling and real-world application architecture.

---

## 📖 About Laravel

Laravel is a web application framework with expressive and elegant syntax.  
It simplifies common development tasks such as routing, database management, authentication and background jobs, allowing developers to focus on building robust applications.

Laravel provides a powerful ecosystem that fits both small projects and large-scale systems.

Learn more at: [https://laravel.com](https://laravel.com)

---

## 📄 License

This project is open-sourced software licensed under the **MIT license**.

You are free to use, modify and distribute this software according to the terms of the license.

---

## 📌 Project Status

🚧 **In development**  
New features and improvements are continuously being added.
