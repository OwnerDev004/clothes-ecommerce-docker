## Running with Docker

This project includes Docker and Docker Compose setup for easy deployment and local development.

### Requirements
- Docker (latest recommended)
- Docker Compose (v2+)
- Node.js version: `22.13.1` (as specified in Dockerfile)

### Build and Run Instructions

1. **Build and start the app:**
   ```bash
   docker compose up --build
   ```
   This will build the image using the provided Dockerfile and start the service.

2. **Access the app:**
   - The application will be available at [http://localhost:3000](http://localhost:3000)
   - Port `3000` is exposed by default (see `docker-compose.yml` and Dockerfile)

### Environment Variables
- No environment variables are required by default.
- If you need to set custom environment variables, create a `.env` file and uncomment the `env_file` line in `docker-compose.yml`.

### Special Configuration
- The container runs as a non-root user (`appuser`) for security.
- No volumes or persistent storage are configured by default.
- The service is attached to the `appnet` bridge network.

### Service Details
- **Service name:** `typescript-app`
- **Port exposed:** `3000`
- **Restart policy:** `unless-stopped`

For any additional configuration, refer to the `docker-compose.yml` and `Dockerfile` in the project root.