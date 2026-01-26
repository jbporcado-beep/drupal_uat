# Deploy with Portainer Docker Stack

## Simple Workflow: GitHub → Portainer Stack

### 1. Push to GitHub

```bash
git add .
git commit -m "Your message"
git push origin main
```

### 2. Deploy in Portainer

1. Go to **Stacks** in Portainer
2. Click **Add stack**
3. Choose **Repository** (or **Web editor** if you paste the compose file)
4. Enter your GitHub repo URL
5. Select **docker-compose.stack.yml** as the compose file
6. Click **Deploy the stack**

Portainer will:
- Clone your repo
- Build the image from the Dockerfile
- Start all services

### 3. Environment Variables

Make sure to create a `.env` file in your repo root (or set them in Portainer's environment section) with values from `.env.example`:

```
GPG_RECIPIENT_KEY=
CIC_HOST=
CIC_SUBMISSION_DIR=
CIC_PORT=
FTPS_PW_ENCRYPT_KEY=
TEST_USERNAME=
TEST_PASSWORD=
```

**Note:** Never commit `.env` to GitHub (it's in `.gitignore`). Set these in Portainer's stack environment variables or use Portainer's secrets feature.

---

## Alternative: Manual Docker Stack Deploy

If deploying manually (not via Portainer):

```bash
# Clone repo
git clone <your-repo-url>
cd PCDISS

# Create .env from example
cp .env.example .env
# Edit .env with your values

# Deploy stack
docker stack deploy -c docker-compose.stack.yml pcdiss
```

---

## Files

- **`docker-compose.yaml`** - For local `docker compose up` (same as stack file, but kept separate for clarity)
- **`docker-compose.stack.yml`** - For Portainer or `docker stack deploy` (uses `build:` so Portainer builds automatically)
- **`.env.example`** - Template for environment variables
