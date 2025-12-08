-- Seed 10M course events using generate_series
INSERT INTO course_events (
    event_type,
    student_id,
    course_id,
    term_id,
    instructor_id,
    resource_type,
    resource_id,
    event_data,
    occurred_at,
    created_at
)
SELECT
    CASE (random() * 4)::int
        WHEN 0 THEN 'page_view'
        WHEN 1 THEN 'video_watch'
        WHEN 2 THEN 'quiz_attempt'
        WHEN 3 THEN 'discussion_post'
        ELSE 'file_download'
    END,
    (random() * 4999 + 1)::int, -- student_id (1-5000)
    (random() * 99 + 1)::int,    -- course_id (1-100)
    (random() * 3 + 1)::int,     -- term_id (1-4)
    (random() * 199 + 1)::int,   -- instructor_id (1-200)
    CASE (random() * 5)::int
        WHEN 0 THEN 'video'
        WHEN 1 THEN 'quiz'
        WHEN 2 THEN 'assignment'
        WHEN 3 THEN 'page'
        WHEN 4 THEN 'forum'
        ELSE 'file'
    END,
    (random() * 999 + 1)::int,   -- resource_id
    '{"duration_seconds": 300}'::jsonb,
    NOW() - (random() * interval '365 days'),
    NOW()
FROM generate_series(1, 10000000) AS i;
