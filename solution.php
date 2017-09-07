<?php
//  Robin Arenson (robin.arenson@gmail.com)

function parse_request($request, $secret)
{
    $request = strtr($request, '-_', '+/');

    $parts = explode('.', $request);

    if (count($parts) < 2) {
        return false;
    }

    return json_decode(
        base64_decode(
            $parts[1]
        ), true
    );
}

function dates_with_at_least_n_scores($pdo, $n)
{
    // YOUR CODE GOES HERE
}

function users_with_top_score_on_date($pdo, $date)
{
    // YOUR CODE GOES HERE
}

function times_user_beat_overall_daily_average($pdo, $user_id)
{
    // YOUR CODE GOES HERE
}
