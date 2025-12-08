-- Seed 1M course events for quick testing
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
    (random() * 4999 + 1)::int,
    (random() * 99 + 1)::int,
    (random() * 3 + 1)::int,
    (random() * 199 + 1)::int,
    CASE (random() * 5)::int
        WHEN 0 THEN 'video'
        WHEN 1 THEN 'quiz'
        WHEN 2 THEN 'assignment'
        WHEN 3 THEN 'page'
        WHEN 4 THEN 'forum'
        ELSE 'file'
    END,
    (random() * 999 + 1)::int,
    '{"duration_seconds": 300}'::jsonb,
    NOW() - (random() * interval '365 days'),
    NOW()
FROM generate_series(1, 1000000) AS i;

-- Create aggregated summary data
INSERT INTO course_daily_activity (
    report_date,
    course_id,
    term_id,
    total_events,
    unique_students,
    page_views,
    video_minutes,
    quiz_attempts,
    discussion_posts,
    avg_session_duration_minutes,
    created_at,
    updated_at
)
SELECT
    DATE(occurred_at) as report_date,
    course_id,
    term_id,
    COUNT(*) as total_events,
    COUNT(DISTINCT student_id) as unique_students,
    SUM(CASE WHEN event_type = 'page_view' THEN 1 ELSE 0 END) as page_views,
    SUM(CASE WHEN event_type = 'video_watch' THEN 5 ELSE 0 END) as video_minutes,
    SUM(CASE WHEN event_type = 'quiz_attempt' THEN 1 ELSE 0 END) as quiz_attempts,
    SUM(CASE WHEN event_type = 'discussion_post' THEN 1 ELSE 0 END) as discussion_posts,
    15.5 as avg_session_duration_minutes,
    NOW(),
    NOW()
FROM course_events
GROUP BY DATE(occurred_at), course_id, term_id;
