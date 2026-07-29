FROM node:20-alpine

WORKDIR /app

COPY package.json package-lock.json ./

# Remove old node_modules if they exist
RUN rm -rf node_modules

RUN npm ci --legacy-peer-deps

COPY . .

EXPOSE 3000

# Build and serve at runtime (build is done inside the mounted volume
# so .output/ is available when the container runs with volume mounts)
CMD ["sh", "-c", "NODE_OPTIONS=\"--max-old-space-size=4096\" npm run build && node .output/server/index.mjs"]
