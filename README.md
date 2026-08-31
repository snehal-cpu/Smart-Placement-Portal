# 🎓 Smart Placement Portal

A full-stack web-based **Smart Placement Portal** designed to simplify the placement process by connecting **students and recruiters** on a centralized platform.

Students can build and manage their profiles, explore job opportunities, apply for jobs, and track their application status. Recruiters can manage their company profiles, post job opportunities, manage jobs, and view applicants.

---

## 🚀 Features

### 👨‍🎓 Student Portal

- Secure Student Registration and Login
- Role-Based Authentication
- Student Dashboard
- Profile Management
- Resume Management
- Browse Available Job Opportunities
- View Job Details
- Apply for Jobs
- Track Application Status
- View Shortlisted and Selected Applications
- Recent Application Activity

### 🏢 Recruiter Portal

- Recruiter Registration and Login
- Role-Based Authentication
- Recruiter Dashboard
- Company Profile Management
- Post New Job Opportunities
- Manage Posted Jobs
- Edit Job Details
- View Job Applicants
- Manage Candidate Applications

---

## 📊 Dashboard

### Student Dashboard

The student dashboard provides an overview of:

- Total Applications
- Shortlisted Applications
- Selected Applications
- Available Job Opportunities
- Latest Job Opportunities
- Student Profile Summary
- Recent Application Activity

### Recruiter Dashboard

The recruiter portal allows recruiters to:

- Manage their company profile
- Post new job opportunities
- Manage posted jobs
- View applicants
- Manage placement activities

---

## 🔐 Authentication & Security

The project implements:

- Role-Based Access Control
- Student Authentication
- Recruiter Authentication
- Secure Session Management
- Protected Dashboard Pages
- Logout Functionality
- Separate Student and Recruiter Access

---

## 🛠️ Technologies Used

### Frontend

- HTML5
- CSS3
- JavaScript
- Font Awesome
- Google Fonts

### Backend

- PHP

### Database

- MySQL

### Development Environment

- XAMPP
- Apache

---

## 📁 Project Structure

```text
Smart-Placement-Portal/
│
├── config/
│   └── database.php
│
├── includes/
│   ├── auth.php
│   ├── student_sidebar.php
│   └── recruiter_sidebar.php
│
├── student/
│   ├── dashboard.php
│   ├── jobs.php
│   ├── job_details.php
│   ├── applications.php
│   └── profile.php
│
├── recruiter/
│   ├── dashboard.php
│   ├── company_profile.php
│   ├── post_job.php
│   ├── manage_jobs.php
│   └── applicants.php
│
├── uploads/
│
├── index.php
├── login.php
├── register.php
├── logout.php
│
├── .gitignore
└── README.md