<?php
require_once __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Cache PSR-6 minimaliste in-memory.
 * Solution autonome : ne depend que de psr/cache (toujours present dans vendor/).
 * Necessaire car Composer cleanup a supprime google/auth/src/Cache/.
 */
class RProMemoryCacheItem implements CacheItemInterface
{
    private $key;
    private $value;
    private $isHit = false;

    public function __construct(string $key)
    {
        $this->key = $key;
    }
    public function getKey(): string { return $this->key; }
    public function get(): mixed { return $this->value; }
    public function isHit(): bool { return $this->isHit; }
    public function set(mixed $value): static { $this->value = $value; $this->isHit = true; return $this; }
    public function expiresAt(?\DateTimeInterface $expiration): static { return $this; }
    public function expiresAfter(int|\DateInterval|null $time): static { return $this; }
}

class RProMemoryCachePool implements CacheItemPoolInterface
{
    private array $items = [];

    public function getItem(string $key): CacheItemInterface
    {
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }
        return new RProMemoryCacheItem($key);
    }
    public function getItems(array $keys = []): iterable
    {
        $result = [];
        foreach ($keys as $key) { $result[$key] = $this->getItem($key); }
        return $result;
    }
    public function hasItem(string $key): bool { return isset($this->items[$key]); }
    public function clear(): bool { $this->items = []; return true; }
    public function deleteItem(string $key): bool { unset($this->items[$key]); return true; }
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $k) { unset($this->items[$k]); }
        return true;
    }
    public function save(CacheItemInterface $item): bool
    {
        $this->items[$item->getKey()] = $item;
        return true;
    }
    public function saveDeferred(CacheItemInterface $item): bool { return $this->save($item); }
    public function commit(): bool { return true; }
}

function syncTicketsToGoogleSheet($bufferPath, $spreadsheetId, $sheetName, $jsonKeyPath)
{
    $client = new Client();
    // FIX : cache PSR-6 minimaliste inline (autonome, ne depend que de psr/cache)
    $client->setCache(new RProMemoryCachePool());
    $client->setApplicationName('TicketsKin Sync');
    $client->setScopes([Sheets::SPREADSHEETS]);
    $client->setAuthConfig($jsonKeyPath);
    $client->setAccessType('offline');

    $service = new Sheets($client);

    if (!file_exists($bufferPath)) {
        return ['success' => false, 'message' => 'Buffer not found.'];
    }

    $rows = array_map('str_getcsv', file($bufferPath));
    if (count($rows) === 0) {
        return ['success' => true, 'message' => 'Aucun ticket a synchroniser.'];
    }

    $dataToSend = [];
    foreach ($rows as $row) {
        if (count($row) < 7) continue;
        list($date, $heure, $agent, $commune, $marche, $montant, $ticketNum) = $row;
        $dataToSend[] = [
            $ticketNum,
            $marche,
            $commune,
            $montant,
            $date,
            $heure,
            $agent,
            ''
        ];
    }

    $body = new ValueRange([
        'values' => $dataToSend
    ]);

    try {
        $range = "$sheetName!A2";
        $response = $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );
        file_put_contents($bufferPath, '');
        return ['success' => true, 'message' => count($dataToSend) . " tickets synchronises avec succes."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
    }
}

function syncBufferToGoogleSheet($csvPath, $jsonKeyPath, $spreadsheetId) {
    return syncTicketsToGoogleSheet($csvPath, $spreadsheetId, 'Sheet1', $jsonKeyPath);
}
