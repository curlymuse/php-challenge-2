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

    $statement = $pdo->prepare($sql);
    $statement->execute();
    $result = $statement->fetchAll();

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

    $statement = $pdo->prepare($sql);
    $statement->execute([$date, $date]);
    $result = $statement->fetchAll();

    $ids = [];
    foreach ($result as $row) {
        $ids[] = $row['user_id'];
    }

    return $ids;
}

function times_user_beat_overall_daily_average($pdo, $user_id)
{
    $sql = sprintf("
        SELECT COUNT(DISTINCT `date`) as total
            FROM scores AS s1
            WHERE user_id = %d
            AND 
            (
                SELECT MAX(score)
                  FROM scores AS s3
                  WHERE user_id = %d
                  AND s3.`date` = s1.`date`
            ) >
            (
              SELECT AVG(score)
                FROM scores AS s2
                WHERE s1.`date` = s2.`date`
            )
    ", $user_id, $user_id);

    $statement = $pdo->prepare($sql);
    $statement->execute();
    $result = $statement->fetchAll();

    return $result[0]['total'];
}
