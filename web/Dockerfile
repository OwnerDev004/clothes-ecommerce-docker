FROM node:20-alpine

WORKDIR /app

COPY package.json package-lock.json ./

# Remove old node_modules if they exist
RUN rm -rf node_modules

RUN npm ci --legacy-peer-deps

COPY . .

EXPOSE 3000

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
