# LF E-commerce Reporting Module

> **High-Volume Reporting System** for E-commerce Order Ledger  
> Built with Laravel 12, React, PostgreSQL, and Laravel Octane (Swoole)

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)
![React](https://img.shields.io/badge/React-18-blue.svg)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-blue.svg)

---

## 🎯 Project Overview

A blazing-fast, enterprise-grade reporting module capable of processing **tens of millions of rows** and generating:

- ✅ **Viewable reports** in web UI (paged & searchable)
- ✅ **PDF exports** supporting 1,000+ pages with Table of Contents
- ✅ **Excel exports** with 1M+ rows using streaming
- ✅ **Asynchronous generation** with real-time progress tracking
- ✅ **Performance**: p95 < 500ms for preview queries under 20 VUs

---

## 🏗️ Architecture

### Technology Stack

**Backend**
- Laravel 12 with Octane (Swoole)
- PHP 8.2
- PostgreSQL 15 (with partitioning)
- Redis (cache & queues)
- spatie/browsershot (PDF generation)
- maatwebsite/excel (Excel generation)

**Frontend**
- React 18
- Vite
- TailwindCSS
- @tanstack/react-virtual (virtualized tables)
- react-hook-form + zod (form validation)
- Chart.js (data visualization)

**Infrastructure**
- Docker Compose (7 services)
- Nginx (reverse proxy)
- Mailhog (email testing)

---

## 📊 Features

### Report Types
1. **Detail Report**: Raw, paginated rows with column selection
2. **Summary Report**: Grouped totals (by day/region/product)
3. **Top-N Report**: Top customers, products, regions by revenue
4. **Exceptions Report**: Failed orders, refunds, SLA breaches
5. **Per-Entity Booklet**: One section per customer/region (1,000+ pages)

### Generation Modes
- **Preview**: Fast, returns first N pages for on-screen review
- **Full Export**: Async background job (PDF/Excel) with progress tracking
- **Scheduling**: CRON-based recurring exports (daily/weekly/monthly)

### PDF Features
- Page headers/footers with title, filters, date, page numbers
- Table of Contents for per-entity booklets
- Automatic page breaks with widow/orphan control
- Repeat table headers on each page
- Optional charts embedded in summaries

### Excel Features
- Multiple sheets (Summary + Details + Charts)
- Streaming writer (no memory issues)
- Proper data types (dates, currency, numbers)
- Freeze panes and auto-filters

---

## 🚀 Quick Start

### Prerequisites
- Docker Desktop
- Git
- 8GB+ RAM recommended

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/andawimarcshelby/LFeCommerce.git
cd LFeCommerce
```

2. **Start Docker services**
```bash
docker-compose up -d
```

3. **Install backend dependencies**
```bash
docker-compose exec api composer install
docker-compose exec api php artisan key:generate
docker-compose exec api php artisan migrate --seed
```

4. **Install frontend dependencies**
```bash
docker-compose exec client npm install
```

5. **Access the application**
- Frontend: http://localhost:5173
- API: http://localhost:8000
- Mailhog: http://localhost:8025

---

## 📁 Project Structure

```
LFeCommerce/
├── backend/                # Laravel 12 API
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Jobs/
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   └── routes/
├── frontend/               # React 18 SPA
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── hooks/
│   │   └── utils/
│   └── public/
├── docker/                 # Docker configuration
│   ├── php/
│   ├── nginx/
│   ├── node/
│   └── postgres/
└── docker-compose.yml
```

---

## 🎨 Design System

### Color Palette
- **Primary Dark**: `#191919`
- **Accent Red**: `#750E21`
- **Accent Orange**: `#E3651D`
- **Accent Green**: `#BED754`

### Typography
- **Font Family**: Inter (Google Fonts)
- **Professional, clean, enterprise-grade design**

---

## 📈 Performance Targets

| Metric | Target | Status |
|--------|--------|--------|
| Data Scale | 10M+ orders, 50M+ line items | ⏳ In Progress |
| Preview Latency (p95) | < 500ms | ⏳ In Progress |
| PDF Generation | 1,000+ pages | ⏳ In Progress |
| Excel Generation | 1M+ rows | ⏳ In Progress |
| Concurrent Exports | 5 simultaneous jobs | ⏳ In Progress |
| Memory per Worker | < 256MB | ⏳ In Progress |

---

## 🔒 Security Features

- Session-based authentication (HttpOnly, Secure, SameSite=Strict)
- Role-Based Access Control (RBAC)
- Signed download URLs with 15-minute TTL
- CSRF protection
- Input validation on all endpoints
- Audit logging for all report generation and downloads
- Rate limiting per user

---

## 🧪 Testing

```bash
# Run backend tests
docker-compose exec api php artisan test

# Run frontend tests
docker-compose exec client npm test

# Load testing with k6
k6 run tests/load/preview-load-test.js
```

---

## 📝 Development Milestones

- [x] **Milestone 1**: Project Foundation & Docker Setup (7%)
- [ ] **Milestone 2**: Database Design & Seeding (20%)
- [ ] **Milestone 3**: Core Reporting Engine & API (40%)
- [ ] **Milestone 4**: Authentication & RBAC (47%)
- [ ] **Milestone 5**: PDF Generation System (60%)
- [ ] **Milestone 6**: Excel Generation System (67%)
- [ ] **Milestone 7**: React UI - Filter & Preview (75%)
- [ ] **Milestone 8**: React UI - Job Status & Downloads (80%)
- [ ] **Milestone 9**: Report Scheduling System (85%)
- [ ] **Milestone 10**: Job Reliability & Recovery (88%)
- [ ] **Milestone 11**: Performance Optimization & Tuning (92%)
- [ ] **Milestone 12**: Load Testing & Validation (95%)
- [ ] **Milestone 13**: Security Hardening (97%)
- [ ] **Milestone 14**: Testing Suite (99%)
- [ ] **Milestone 15**: Documentation & Deployment (100%)

---

## 📚 Documentation

- [Master Plan](docs/master-plan.md)
- [API Documentation](docs/api.md)
- [User Guide](docs/user-guide.md)
- [Admin Guide](docs/admin-guide.md)
- [Deployment Guide](docs/deployment.md)

---

## 👥 Contributing

This is an individual project for academic purposes.

---

## 📄 License

MIT License

---

## 🙏 Acknowledgments

- Laravel Team
- React Team
- PostgreSQL Community
- Swoole Team

---

**Built with ❤️ for enterprise-grade reporting**
