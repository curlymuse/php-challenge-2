<?php
//  Robin Arenson (robin.arenson@gmail.com)

function is_signed_correctly($request, $secret)
{
    $request = strtr($request, '-_', '+/');
    $pieces = explode('.', $request);

    $signature = base64_decode($pieces[0]);
    $payload = base64_decode($pieces[1]);

    $expectedSignature = hash_hmac('sha256', $payload, $secret, true);

    return ($signature == $expectedSignature);
}

function parse_request($request, $secret)
{
    if (! is_signed_correctly($request, $secret)) {
        return false;
    }

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
    $sql = sprintf("
        SELECT `date`, COUNT(*) AS total
            FROM scores
            GROUP BY `date`
              HAVING total >= %s
            ORDER BY `date` DESC
    ", $n);

    $result = select($pdo, $sql);

    $dates = [];
    foreach ($result as $row) {
        $dates[] = $row['date'];
    }

    return $dates;
}

function users_with_top_score_on_date($pdo, $date)
{
    $sql = "
        SELECT user_id
            FROM scores
            WHERE `date` = ?
            AND score = (
              SELECT MAX(score)
                FROM scores
                WHERE `date` = ?
            )
            ORDER BY user_id ASC
    ";

    $result = select($pdo, $sql, [$date, $date]);

    $ids = [];
    foreach ($result as $row) {
        $ids[] = $row['user_id'];
    }

    return $ids;
}

function times_user_beat_overall_daily_average($pdo, $user_id)
{
    $sql = "
        SELECT COUNT(*) as total
            FROM (
              SELECT AVG(score) AS daily_average, `date`
                FROM scores
                GROUP BY `date`
            ) all_averages
            INNER JOIN (
              SELECT AVG(score) AS user_average, `date`
                FROM scores
                WHERE user_id = ?
                GROUP BY `date`
            ) user_averages
            ON all_averages.`date` = user_averages.`date`
            WHERE user_averages.user_average > all_averages.daily_average
    ";

    $result = select($pdo, $sql, [$user_id]);

    return $result[0]['total'];
}

function select($pdo, $sql, $args = [])
{
    $statement = $pdo->prepare($sql);
    $statement->execute($args);

    return $statement->fetchAll();
}
