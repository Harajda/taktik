<?php

return [
    'users' => [
        'filters' => ['id', 'name', 'email', 'created_at', 'updated_at'],
        'sorts' => ['id', 'name', 'email', 'created_at', 'updated_at'],
        'group_by' => ['id', 'name', 'email', 'created_at', 'updated_at'],
    ],
    'comments' => [
        'filters' => ['id', 'content', 'user_id', 'commentable_id', 'commentable_type', 'created_at', 'updated_at'],
        'sorts' => ['id', 'content', 'user_id', 'created_at', 'updated_at'],
        'group_by' => ['id', 'content', 'user_id', 'commentable_id', 'commentable_type', 'created_at', 'updated_at'],
    ],
    'categories' => [
        'filters' => ['id', 'name', 'created_at', 'updated_at'],
        'sorts' => ['id', 'name', 'created_at', 'updated_at'],
        'group_by' => ['id', 'name', 'created_at', 'updated_at'],
    ],
    'posts' => [
        'filters' => ['id', 'category_id', 'user_id', 'title', 'created_at', 'updated_at'],
        'sorts' => ['id', 'category_id', 'user_id', 'title', 'created_at', 'updated_at'],
        'group_by' => ['id', 'category_id', 'user_id', 'title', 'created_at', 'updated_at'],
    ],
];
