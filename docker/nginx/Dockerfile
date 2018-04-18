FROM nginx

RUN apt-get update && \
    apt-get install -y vim

COPY nginx.conf /etc/nginx/nginx.conf
ADD symfony.conf /etc/nginx/symfony.conf

CMD ["nginx", "-g", "daemon off;"]

EXPOSE 80