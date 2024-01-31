#!/bin/bash

# Wait for the Redis server to be ready
until redis-cli ping > /dev/null 2>&1
do
    echo "Waiting for Redis server to start..."
    sleep 1
done

echo "Redis server is ready! Connecting..."

redis-cli info