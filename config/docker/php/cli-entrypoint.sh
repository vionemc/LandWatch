#!/bin/sh
set -e

if [ -z "$1" ]; then
    role=${CONTAINER_ROLE:-worker}
	env=${APP_ENV:-production}

	if [ "$env" = "production" ]; then
	    echo "Caching configuration..."
	    (php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear)
	    (php artisan config:cache && php artisan event:cache && php artisan route:cache && php artisan view:cache)
	fi

	if [ "$role" = "queue" ]; then

	    connection=${QUEUE_CONNECTION}
	    queue=${QUEUE_NAME:-default}
	    backoff=${QUEUE_BACKOFF:-0}
	    max_jobs=${QUEUE_MAX_JOBS:-0}
	    max_time=${QUEUE_MAX_TIME:-0}
	    memory=${QUEUE_MEMORY:-128}
	    sleep=${QUEUE_SLEEP:-3}
	    rest=${QUEUE_REST:-0}
	    timeout=${QUEUE_TIMEOUT:-60}
	    tries=${QUEUE_TRIES:-1}

	    echo "Running queue worker..."
	    exec php artisan queue:work "$connection" \
	        --queue="$queue" \
	        --backoff="$backoff" \
	        --max-jobs="$max_jobs" \
	        --max-time="$max_time" \
	        --memory="$memory" \
	        --sleep="$sleep" \
	        --rest="$rest" \
	        --timeout="$timeout" \
	        --tries="$tries" \
	        --no-interaction \
	        -vv

	elif [ "$role" = "cron" ]; then

	    echo "Running scheduled tasks..."
	    while true
	    do
	        php artisan schedule:run --no-interaction -vv
	        sleep 60
        done

    fi
else
    # first arg is `-f` or `--some-option`
    if [ "${1#-}" != "$1" ]; then
        set -- php "$@"
    fi
    exec "$@"
fi
