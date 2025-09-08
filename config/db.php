<?php
// Supabase REST configuration and helper functions

// Configure via environment variables when possible
$SUPABASE_URL = getenv('SUPABASE_URL') ?: 'https://hdpyjddmzxbbzycriyar.supabase.co';
$SUPABASE_SERVICE_ROLE_KEY = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImhkcHlqZGRtenhiYnp5Y3JpeWFyIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc1NzMxNDc4MywiZXhwIjoyMDcyODkwNzgzfQ.nM7yovM3RDpr9tz8N1KdgZTZprDXNdE668xs0vlCIU4';

/**
 * Perform a Supabase REST request.
 * @param string $method HTTP method: GET, POST, PATCH, DELETE
 * @param string $endpoint Table endpoint like 'super_admin'
 * @param array $queryParams Query params e.g. ['select' => 'id,name', 'username' => 'eq.john']
 * @param array|null $body JSON-serializable body for POST/PATCH
 * @param array $extraHeaders Additional headers like ['Prefer: return=representation']
 * @return array [statusCode, decodedJson|null, rawBody]
 */
function supabase_request(string $method, string $endpoint, array $queryParams = [], ?array $body = null, array $extraHeaders = []): array {
    global $SUPABASE_URL, $SUPABASE_SERVICE_ROLE_KEY;

    $url = rtrim($SUPABASE_URL, '/') . '/rest/v1/' . ltrim($endpoint, '/');
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }

    $ch = curl_init($url);
    $headers = array_merge([
        'apikey: ' . $SUPABASE_SERVICE_ROLE_KEY,
        'Authorization: Bearer ' . $SUPABASE_SERVICE_ROLE_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
    ], $extraHeaders);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [$httpCode ?: 0, null, $curlErr];
    }

    $decoded = json_decode($response, true);
    return [$httpCode, $decoded, $response];
}

/**
 * Select rows from a table.
 * @param string $table Table name
 * @param array $filters e.g. ['username' => 'eq.john']
 * @param string $select columns to select
 * @param array $options extra query params like ['limit' => 1]
 */
function supabase_select(string $table, array $filters = [], string $select = '*', array $options = []): array {
    $params = array_merge(['select' => $select], $options);
    foreach ($filters as $column => $opValue) {
        $params[$column] = $opValue; // e.g. 'eq.john'
    }
    return supabase_request('GET', $table, $params);
}

/**
 * Insert a single row.
 */
function supabase_insert(string $table, array $row): array {
    // Prefer return representation to get inserted row back
    return supabase_request('POST', $table, [], [$row], ['Prefer: return=representation']);
}

/**
 * Update rows matching filters. Use carefully; typically with primary key filter.
 */
function supabase_update(string $table, array $filters, array $partialRow): array {
    $params = [];
    foreach ($filters as $column => $opValue) {
        $params[$column] = $opValue;
    }
    return supabase_request('PATCH', $table, $params, $partialRow, ['Prefer: return=representation']);
}

/**
 * Activity log helper. Expects a table `activity_log` with columns:
 * id (uuid/serial), actor_type (text), actor_id (bigint or text), action (text), details (text/json), created_at (timestamp default now()).
 */
function log_activity(string $actorType, $actorId, string $action, $details = null): void {
    // Best-effort logging; ignore failures to avoid breaking main flow
    $row = [
        'actor_type' => $actorType,
        'actor_id' => is_numeric($actorId) ? intval($actorId) : strval($actorId),
        'action' => $action,
        'details' => is_array($details) ? json_encode($details) : (string)($details ?? ''),
    ];
    try { supabase_insert('activity_log', $row); } catch (\Throwable $e) { /* noop */ }
}
?>
