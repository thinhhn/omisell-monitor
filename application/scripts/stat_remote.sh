#!/bin/bash

# --- Configuration ---
REMOTE_IP="${REMOTE_CELERY_IP:-10.148.0.26}"
REMOTE_USER="${REMOTE_CELERY_USER:-thinhhn}"
CODE_DIR="${REMOTE_CELERY_CODE_DIR:-/data/code/omisell-backend}"
VENV_PYTHON="${REMOTE_CELERY_VENV_PYTHON:-/data/venv/omisell3.11/bin/python}"

# SSH key logic
SSH_KEY=""
[ -f ~/.ssh/thinhhn_id_rsa ] && SSH_KEY="-i ~/.ssh/thinhhn_id_rsa"
[ -f ~/.ssh/id_rsa ] && [ -z "$SSH_KEY" ] && SSH_KEY="-i ~/.ssh/id_rsa"

ssh $SSH_KEY -o LogLevel=ERROR -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_IP" "cd $CODE_DIR && sudo $VENV_PYTHON -c \"
import json
import os
import django
from celery import Celery

# Setup Django environment
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'omisell.settings')
django.setup()

app = Celery('omisell')
app.config_from_object('django.conf:settings', namespace='CELERY')

stats = {}

with app.connection_or_acquire() as conn:
    # 1. Lấy danh sách tất cả các queue đang hoạt động từ Worker
    i = app.control.inspect()
    active_queues_map = i.active_queues() or {}
    
    unique_queue_names = set()
    for worker_queues in active_queues_map.values():
        for q in worker_queues:
            unique_queue_names.add(q['name'])

    # 2. Truy vấn Broker để lấy message_count chính xác (giống logic project)
    for name in unique_queue_names:
        try:
            # passive=True giúp lấy thông tin mà không tạo mới queue
            ok_nt = conn.default_channel.queue_declare(queue=name, passive=True)
            count = ok_nt.message_count
            if count > 0:
                stats[name] = count
        except Exception:
            continue

print(json.dumps(stats, indent=4))
\""