# 🎓 Smart Placement Portal

A web-based **Smart Placement Portal** designed to connect students and recruiters through a centralized placement management system.

The platform allows recruiters to post and manage job opportunities while students can create profiles, explore available jobs, apply for opportunities, and track their application status.

---

## 🚀 Features

### 👨‍🎓 Student Module

- Student Registration and Login
- Secure Authentication
- Student Dashboard
- Profile Management
- View Available Jobs
- Apply for Jobs
- Track Application Status
- View Shortlisted Applications
- View Selected Applications
- Resume Management
- Recent Application Activity

---

### 🏢 Recruiter Module

- Recruiter Registration and Login
- Recruiter Dashboard
- Company Profile Management
- Post New Jobs
- Manage Posted Jobs
- Edit Job Details
- View Applicants
- Manage Student Applications

---

### 🔐 Authentication & Security

- Role-Based Authentication
- Student Access Control
- Recruiter Access Control
- Secure Session Management
- Protected Dashboard Pages
- Password-Based Login System

---

## 📊 Dashboard Features

### Student Dashboard

The student dashboard provides:

- Total Applications
- Shortlisted Applications
- Selected Applications
- Available Job Opportunities
- Latest Job Opportunities
- Student Profile Summary
- Recent Application Activity

### Recruiter Dashboard

The recruiter dashboard provides:

- Job Management
- Applicant Management
- Company Profile Access
- Job Posting Features
- Placement Activity Overview

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
- Apache Server

---

## 📁 Project Structure

```text
SmartPlacementPortal/
│
├── admin/
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
├── login.php
├── register.php
├── logout.php
├── index.php
│
└── README.md