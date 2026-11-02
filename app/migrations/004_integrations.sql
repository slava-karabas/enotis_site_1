CREATE TABLE integration_outbox (
 id TEXT PRIMARY KEY, event_type TEXT NOT NULL, object_id INTEGER, payload TEXT NOT NULL,
 channel TEXT NOT NULL CHECK(channel IN ('email','webhook')), state TEXT NOT NULL DEFAULT 'pending' CHECK(state IN ('pending','sent','failed')),
 attempts INTEGER NOT NULL DEFAULT 0, next_at INTEGER NOT NULL DEFAULT 0, lease_until INTEGER NOT NULL DEFAULT 0,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, sent_at TEXT, last_error TEXT
);
CREATE INDEX integration_pending ON integration_outbox(state,next_at,lease_until);
