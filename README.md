# UiTM Schedule Scraper API - Railway Deployment

This is a standalone scraping API service designed to be deployed on Railway.app (FREE tier).

## 🚀 Quick Start

### 1. Deploy to Railway

1. Go to [Railway.app](https://railway.app) and sign up (FREE)
2. Click "New Project" → "Deploy from GitHub repo"
3. Upload this `railway-scraper-api` folder
4. Railway will automatically detect and deploy

### 2. Configure Environment Variables

In Railway dashboard, go to your project → Variables:

```env
SCRAPER_API_KEY=your-random-secret-key-12345678
DUSK_HEADLESS_DISABLED=false
DUSK_DRIVER_URL=http://localhost:9515
```

**Important:** Change `SCRAPER_API_KEY` to a random string!

### 3. Wait for Deployment

- Railway will install PHP, Composer dependencies, and Chromium
- First deployment takes 3-5 minutes
- You'll get a public URL: `https://your-app.up.railway.app`

### 4. Test the API

**Health Check:**
```bash
curl https://your-app.up.railway.app/
```

**Scrape Test:**
```bash
curl -X POST https://your-app.up.railway.app/api/scrape \
  -H "Content-Type: application/json" \
  -d '{
    "matric": "2024767365",
    "api_key": "your-random-secret-key-12345678"
  }'
```

## 📝 Integration with Main Laravel App

### Update `.env` on cPanel:

```env
USE_RAILWAY_SCRAPER=true
RAILWAY_SCRAPER_URL=https://your-app.up.railway.app
SCRAPER_API_KEY=your-random-secret-key-12345678
```

**That's it!** Your cPanel website will now use Railway for scraping.

## 🔧 How It Works

```
User clicks Sync
    ↓
Laravel on cPanel
    ↓
HTTP POST to Railway API
    ↓
Railway runs ChromeDriver + Scraper
    ↓
Generates reCAPTCHA token
    ↓
Scrapes uitm-schedule.live
    ↓
Returns JSON data
    ↓
Laravel saves to database
    ↓
Success! ✅
```

## 📊 API Endpoints

### `GET /`
Health check endpoint

**Response:**
```json
{
  "service": "UiTM Schedule Scraper API",
  "status": "running",
  "version": "1.0.0"
}
```

### `POST /api/scrape`
Scrape timetable for a matric number

**Request:**
```json
{
  "matric": "2024767365",
  "api_key": "your-secret-key"
}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "code": "HRM643",
      "day": "Monday",
      "time": "8:00 AM - 10:00 AM",
      "course": "Performance Management",
      "room": "DK5-1-01",
      "lecturer": "DR. ROZIYANA",
      "group": "AM2404A"
    }
  ],
  "count": 13
}
```

## 💰 Railway Free Tier

- **500 hours/month** runtime (enough for 1000+ scrapes)
- **$5 credit/month** (usually enough)
- Automatically sleeps when inactive
- Wakes up on first request (2-3 seconds delay)

## 🔒 Security

- API key authentication required
- CORS enabled for your domain
- No data stored (stateless)
- SSL/HTTPS by default

## 📁 Files Structure

```
railway-scraper-api/
├── index.php              # Main entry point & routing
├── composer.json          # PHP dependencies
├── .env                   # Environment configuration
├── railway.json          # Railway deployment config
├── nixpacks.toml         # Nixpacks build config
└── app/
    ├── ScraperService.php    # Main scraping logic
    ├── StealthHelper.php     # Fingerprint spoofing
    └── DuskBrowser.php       # Chrome configuration
```

## 🐛 Troubleshooting

### "Service unavailable" error
- Check Railway logs in dashboard
- Verify ChromeDriver is installed
- Restart the service

### "Unauthorized" error
- Check API key matches in both Railway and cPanel `.env`
- Ensure no extra spaces in API key

### Slow first request
- Railway sleeps after inactivity
- First request takes 5-10 seconds (waking up)
- Subsequent requests are fast (<5 seconds)

## 🎯 Testing Locally

```bash
# Install dependencies
composer install

# Copy environment
cp .env.example .env

# Start ChromeDriver
chromedriver --port=9515

# Start PHP server
php -S localhost:8000 index.php

# Test
curl -X POST http://localhost:8000/api/scrape \
  -H "Content-Type: application/json" \
  -d '{"matric":"2024767365","api_key":"test"}'
```

## 📞 Support

If Railway deployment fails, check:
1. Railway build logs
2. Environment variables are set
3. Chromium is installed (automatic in Railway)

## ✅ Deployment Checklist

- [ ] Railway account created
- [ ] Project deployed from this folder
- [ ] Environment variables configured
- [ ] API key is random and secure
- [ ] Test endpoint works
- [ ] cPanel `.env` updated with Railway URL
- [ ] Test sync from website

---

**Ready to deploy!** 🚀 Just upload to Railway and add the URL to cPanel!
