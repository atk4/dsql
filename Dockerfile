FROM atk4/image

WORKDIR /app
ADD . /app
RUN mkdir -p build/logs

CMD vendor/bin/phpunit

