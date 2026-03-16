# 🌍 Tafakari Digital Hub

A premium, full-stack digital intelligence and community engagement platform serving **Kenya**, **Ethiopia**, and the **Democratic Republic of Congo (DRC)**. Built to centralise knowledge, visualise regional data, and empower communities through technology.

---

## ✨ Features

### 🗺️ Interactive Regional Heatmap

- **49 strategic data points** across Kenya (16), Ethiopia (16), and DRC (17)
- Real-time severity markers with intensity-based colour coding
- Dynamic regional scorecard (Total Reports, Avg Stress, Top Focus Area)
- Per-region detailed popups with category, description, and intensity score
- Country filter controls with animated map flyTo transitions

### 🖼️ Photo Gallery

- 4 regional photo albums (Nairobi, Addis Ababa, Goma, Mombasa)
- **Country popup modal** — click any album to browse all photos
- Full-screen **lightbox** with prev/next navigation and thumbnail strip
- Cinematic hover animations with gradient overlays

### 🎥 Video Library

- 4 categorised video reports (Governance, Economic, Climate, Security)
- Cinematic thumbnails with **category-specific colour-grade overlays**
- Duration, views, and country/category badges per card

### 🔐 Authentication Portal

- **Split-screen login page** — marketing carousel on the left, form on the right
- Auto-advancing carousel with crossfade transitions and dot indicators
- Isolated from admin sidebar using Next.js Route Groups (`(auth)/login`)
- Redirects to admin dashboard post-login

### 📰 Content Management

- Admin dashboard for editorial content creation and management
- Super Admin approval and moderation workflows
- Draft → Pending → Published → Rejected → Archived content lifecycle

### 🎙️ Podcasts & Documents

- Audio library with simulated players and regional tagging
- Searchable document repository with download tracking

---

## 🛠️ Tech Stack

| Layer     | Technology                                     |
| --------- | ---------------------------------------------- |
| Framework | [Next.js 14](https://nextjs.org/) (App Router) |
| Language  | TypeScript                                     |
| Styling   | Tailwind CSS                                   |
| Maps      | Leaflet + react-leaflet                        |
| Auth      | NextAuth.js (Credentials Provider)             |
| Images    | Next.js `Image` component                      |
| Icons     | Unicode / Emoji icons                          |

---

## 🎨 Design System

- **Primary colour**: Emerald Green (`#10b981`)
- **Secondary**: White & Slate (`#0f172a`)
- **Typography**: Outfit (headings) + Inter (body)
- **UI Style**: Glassmorphism, smooth gradients, and micro-animations

---

## 📁 Project Structure

```
src/
├── app/
│   ├── (auth)/login/         # Sidebar-free login page
│   ├── admin/                # Admin portal (sidebar layout)
│   │   ├── dashboard/
│   │   ├── content/
│   │   └── super/
│   ├── gallery/              # Photo gallery with popup lightbox
│   ├── heatmap/              # Regional heatmap page
│   ├── podcasts/
│   ├── videos/
│   └── documents/
├── components/
│   ├── layout/
│   │   ├── Navbar.tsx
│   │   └── AdminSidebar.tsx
│   └── ui/
│       ├── Map.tsx           # Leaflet regional heatmap engine
│       └── HomeCarousel.tsx
└── public/
    └── gallery_*.png         # Regional imagery assets
```

---

## 🚀 Getting Started

### Prerequisites

- Node.js 18+
- npm or yarn

### Installation

```bash
git clone https://github.com/sammy001-svg/tafakari-digital-hub.git
cd tafakari-digital-hub
npm install
```

### Environment Variables

Create a `.env.local` file in the root:

```env
NEXTAUTH_SECRET=your-secret-here
NEXTAUTH_URL=http://localhost:3000
```

### Development

```bash
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) in your browser.

### Build

```bash
npm run build
npm start
```

---

## 🌐 Target Geographies

| Country     | Key Regions Covered                                                                                                                                    |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 🇰🇪 Kenya    | Nairobi, Mombasa, Kisumu, Eldoret, Nakuru, Machakos, Malindi, Garissa, Lodwar, Kakamega, Meru, Isiolo, Narok, Voi, Lamu, Marsabit                      |
| 🇪🇹 Ethiopia | Addis Ababa, Gonder, Bahir Dar, Mekele, Hawassa, Dire Dawa, Jimma, Adama, Dessie, Jijiga, Shashemene, Arba Minch, Gambela, Asosa, Semera, Debre Markos |
| 🇨🇩 DRC      | Kinshasa, Goma, Lubumbashi, Kisangani, Kananga, Bukavu, Mbuji-Mayi, Matadi, Beni, Boma, Mbandaka, Bandundu, Isiro, Kindu, Kalemie, Kamina, Lisala      |

---

## 📄 Licence

This project is private and proprietary to Tafakari Digital Hub.

---

> Built with ❤️ for the Great Lakes Region of Africa.
