#!/bin/bash

# --- Cấu hình ---
# SSH key được config trong ~/.ssh/config, không cần truyền vào
# Load từ environment variables nếu có

REMOTE_IP="${REMOTE_CELERY_IP:-10.148.0.26}"
REMOTE_USER="${REMOTE_CELERY_USER:-thinhhn}"
CODE_DIR="${REMOTE_CELERY_CODE_DIR:-/data/code/omisell-backend}"
VENV_PYTHON="${REMOTE_CELERY_VENV_PYTHON:-/data/venv/omisell3.11/bin/python}"

# Debug: Print environment
echo "DEBUG: USER=$(whoami), HOME=$HOME, IP=$REMOTE_IP, USER=$REMOTE_USER" >&2

# Kiểm tra các biến bắt buộc
if [ -z "$REMOTE_IP" ] || [ -z "$REMOTE_USER" ] || [ -z "$CODE_DIR" ] || [ -z "$VENV_PYTHON" ]; then
    echo "{\"error\": \"Missing required environment variables\"}" >&2
    exit 1
fi

# Debug: Check SSH key availability
if [ -f ~/.ssh/id_rsa ]; then
    echo "DEBUG: SSH key found at ~/.ssh/id_rsa" >&2
else
    echo "DEBUG: SSH key NOT found at ~/.ssh/id_rsa" >&2
fi

# Debug: Check SSH config
if [ -f ~/.ssh/config ]; then
    echo "DEBUG: SSH config found" >&2
else
    echo "DEBUG: SSH config NOT found" >&2
fi

ssh -o LogLevel=ERROR -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$REMOTE_IP" "cd $CODE_DIR && sudo $VENV_PYTHON -c \"
import json
from omisell.celery import app

# Khởi tạo inspect
ins = app.control.inspect()

# Lấy các task đang chờ xử lý trong queue (đã được worker nhận nhưng chưa chạy)
reserved = ins.reserved() or {}
# Lấy các task đang chạy
active = ins.active() or {}
# Lấy các task đã lên lịch
scheduled = ins.scheduled() or {}

stats = {}

def count_tasks(data):
    for worker, tasks in data.items():
        for task in tasks:
            # Lấy tên queue của task
            q_name = task.get('delivery_info', {}).get('routing_key', 'unknown')
            stats[q_name] = stats.get(q_name, 0) + 1

count_tasks(reserved)
count_tasks(active)
count_tasks(scheduled)

print(json.dumps(stats, indent=4))
\"" 2>&1 | grep -A 9999 '^{'