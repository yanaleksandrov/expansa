<?php

declare(strict_types=1);

use Expansa\Facades\Hook;
use Expansa\Mail\Mailer;
use Expansa\Scheduler\Cron\CronExpression;
use Expansa\Scheduler\Exceptions\SchedulerException;
use Expansa\Scheduler\Job;
use Expansa\Scheduler\Scheduler;
use PHPMailer\PHPMailer\PHPMailer;

// run: php tests/Scheduler.php
require_once __DIR__ . '/bootstrap.php';

function runDate(string $expression, string $from, bool $invert = false): string
{
    $date = new DateTime($from, new DateTimeZone('UTC'));
    $cron = new CronExpression($expression);

    return ($invert ? $cron->getPreviousRunDate($date) : $cron->getNextRunDate($date))->format('Y-m-d H:i');
}

function expression(Job $job): string
{
    return $job->getExecutionTime()->getExpression();
}

$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-scheduler-' . getmypid();
mkdir($tmp);

// cron expression
check('isDue ignores seconds', new CronExpression('30 10 * * *')->isDue(new DateTime('2025-01-01 10:30:59')));
check('isDue is false a minute later', ! new CronExpression('30 10 * * *')->isDue(new DateTime('2025-01-01 10:31')));
check('isDue accepts a string and a timezone', new CronExpression('0 12 * * *')->isDue('2025-01-01 09:00 UTC', 'Europe/Moscow'));
check('isDue converts a date to the given timezone', new CronExpression('0 12 * * *')->isDue(new DateTimeImmutable('2025-01-01 09:00', new DateTimeZone('UTC')), 'Europe/Moscow'));
check('either day of month or day of week satisfies the day', new CronExpression('0 0 13 * 5')->isDue(new DateTime('2025-06-13')) && new CronExpression('0 0 13 * 5')->isDue(new DateTime('2025-06-06')) && ! new CronExpression('0 0 13 * 5')->isDue(new DateTime('2025-06-07')));
check('? leaves only the other day field', new CronExpression('0 0 ? * 5')->isDue(new DateTime('2025-06-06')) && ! new CronExpression('0 0 ? * 5')->isDue(new DateTime('2025-06-07')));
check('L is the last day of the month', new CronExpression('0 0 L * *')->isDue(new DateTime('2024-02-29')) && ! new CronExpression('0 0 L * *')->isDue(new DateTime('2024-02-28')));
check('15W moves a Saturday to Friday', new CronExpression('0 0 15W * *')->isDue(new DateTime('2025-03-14')) && ! new CronExpression('0 0 15W * *')->isDue(new DateTime('2025-03-15')));
check('1W does not leave the month', new CronExpression('0 0 1W * *')->isDue(new DateTime('2025-03-03')));
check('31W matches nothing in a shorter month', ! new CronExpression('0 0 31W * *')->isDue(new DateTime('2023-02-03')) && ! new CronExpression('0 0 31W * *')->isDue(new DateTime('2023-02-28')));
check('5L is the last Friday', new CronExpression('0 0 * * 5L')->isDue(new DateTime('2025-01-31')) && ! new CronExpression('0 0 * * 5L')->isDue(new DateTime('2025-01-24')));
check('5#2 is the second Friday', new CronExpression('0 0 * * 5#2')->isDue(new DateTime('2025-01-10')) && ! new CronExpression('0 0 * * 5#2')->isDue(new DateTime('2025-01-17')));
check('0 and 7 are both Sunday', new CronExpression('0 0 * * 0')->isDue(new DateTime('2025-01-05')) && new CronExpression('0 0 * * 7')->isDue(new DateTime('2025-01-05')));
check('literal ranges starting with Sunday match', new CronExpression('0 0 * * SUN-WED')->isDue(new DateTime('2025-01-05')) && ! new CronExpression('0 0 * * SUN-WED')->isDue(new DateTime('2025-01-09')));
check('month literals match', new CronExpression('0 0 1 JAN-MAR *')->isDue(new DateTime('2025-02-01')) && ! new CronExpression('0 0 1 JAN-MAR *')->isDue(new DateTime('2025-04-01')));
check('stepped ranges match', new CronExpression('5-20/5 * * * *')->isDue(new DateTime('2025-01-01 00:15')) && ! new CronExpression('5-20/5 * * * *')->isDue(new DateTime('2025-01-01 00:25')));

check('next run date', runDate('0 9-17 * * 1-5', '2025-01-03 17:30') === '2025-01-06 09:00');
check('previous run date', runDate('0 9-17 * * 1-5', '2025-01-06 08:30', true) === '2025-01-03 17:00');
check('next run date of an unsorted hour list', runDate('0 5,2 * * *', '2024-01-01 01:00') === '2024-01-01 02:00');
check('next run date of an unsorted minute list', runDate('*/20,5 * * * *', '2024-01-01 01:01') === '2024-01-01 01:05');
check('next run date of a step equal to the range', runDate('*/59 * * * *', '2024-01-01 00:59') === '2024-01-01 01:00');
check('next run date with either day', runDate('0 0 13 * 5', '2025-06-07') === '2025-06-13' . ' 00:00');
check('next run date of the 29th of February', runDate('0 0 29 2 *', '2025-01-01') === '2028-02-29 00:00');
check('allowCurrentDate returns the current date', new CronExpression('* * * * *')->getNextRunDate(new DateTime('2025-01-01 10:00'), 0, true)->format('H:i') === '10:00');
check('nth skips matches', new CronExpression('0 * * * *')->getNextRunDate(new DateTime('2025-01-01 10:30'), 2)->format('H:i') === '13:00');
check('multiple run dates are consecutive', array_map(fn ($d) => $d->format('H:i'), new CronExpression('*/20 * * * *')->getMultipleRunDates(3, new DateTime('2025-01-01 10:05'))) === ['10:20', '10:40', '11:00']);
check('impossible expression gives no run dates', new CronExpression('0 0 30 2 *')->getMultipleRunDates(2, new DateTime('2025-01-01')) === []);

$date = new DateTime('2025-01-01 10:30:15');
new CronExpression('0 0 1 * *')->getNextRunDate($date);
new CronExpression('0 0 1 * *')->isDue($date);
check('the given date is never modified', $date->format('Y-m-d H:i:s') === '2025-01-01 10:30:15');

// the hour skipped by the spring DST change still matches the hour after it
$ny = new DateTimeZone('America/New_York');
check('DST: skipped hour runs right after the change', new CronExpression('30 2 * * *')->getNextRunDate(new DateTime('2025-03-09 01:00', $ny))->format('Y-m-d H:i T') === '2025-03-09 03:30 EDT');
check('DST: repeated hour matches both times', new CronExpression('30 1 * * *')->getNextRunDate(new DateTime('2025-11-02 01:40', $ny))->format('Y-m-d H:i T') === '2025-11-02 01:30 EST');

check('aliases resolve', (string) new CronExpression('@daily') === '0 0 * * *' && (string) new CronExpression('@HOURLY') === '0 * * * *');
CronExpression::registerAlias('@workdays', '0 9 * * 1-5');
check('user aliases register', CronExpression::supportsAlias('@workdays') && (string) new CronExpression('@workdays') === '0 9 * * 1-5');
check('user aliases unregister', CronExpression::unregisterAlias('@workdays') && ! CronExpression::supportsAlias('@workdays'));
check('built-in aliases can not be unregistered', throws(fn () => CronExpression::unregisterAlias('@daily'), LogicException::class));
check('invalid aliases are rejected', throws(fn () => CronExpression::registerAlias('daily', '* * * * *'), LogicException::class) && throws(fn () => CronExpression::registerAlias('@x', 'foo'), LogicException::class));

$valid   = ['* * * * *', '*/5 1-5 1,15 JAN-MAR MON-FRI', '0 0 L * ?', '0 0 15W * *', '0 0 ? * 5L', '0 0 * * 5#2', '0 0 * * 7', '05 09 * * *'];
$invalid = ['* * * *', '* * * * * *', '? * * * *', '0 0 ? * ?', '60 * * * *', '0 24 * * *', '0 0 32 * *', '0 0 * 13 *', '0 0 * * 8', '*/0 * * * *', '*/-1 * * * *', '+5 * * * *', '1e1 * * * *', '*/5/2 * * * *', '5/5 * * * *', '1-2-3 * * * *', '*-5 * * * *', '0 0 LW * *', '0 0 1,L * *', '0 0 * * 5#6', 'foo'];
check('valid expressions are accepted', array_filter($valid, fn ($e) => ! CronExpression::isValidExpression($e)) === []);
check('invalid expressions are rejected', array_filter($invalid, fn ($e) => CronExpression::isValidExpression($e)) === []);
check('invalid expression throws a SchedulerException', throws(fn () => new CronExpression('60 * * * *'), SchedulerException::class) && throws(fn () => new CronExpression('60 * * * *'), InvalidArgumentException::class));
check('setPart changes the expression', (string) new CronExpression('* * * * *')->setPart(CronExpression::HOUR, '5') === '* 5 * * *' && new CronExpression('* * * * *')->setPart(CronExpression::HOUR, '5')->isDue(new DateTime('2025-01-01 05:10')));

// the fork must agree with the original library wherever it did not fix a bug
$vendor = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendor) && (require $vendor) !== false && class_exists(Cron\CronExpression::class)) {
    $expressions = ['* * * * *', '0 * * * *', '*/5 * * * *', '15,45 * * * *', '30 2 * * *', '0 1-5/2 * * *', '0 9-17 * * 1-5', '0 0 1 * *', '0 0 L * *', '0 12 15W * *', '0 0 * * 0', '0 0 * * 5L', '0 0 * * 5#2', '0 0 13 * 5', '0 0 * JAN,JUL MON-FRI', '0 0 ? * 1', '59 23 31 12 *', '@weekly'];
    $dates       = [];

    mt_srand(42);
    foreach (['UTC', 'America/New_York', 'Europe/London', 'Australia/Lord_Howe'] as $zone) {
        $zone = new DateTimeZone($zone);
        for ($i = 0; $i < 40; $i++) {
            $dates[] = new DateTime('@' . mt_rand(1_700_000_000, 1_770_000_000))->setTimezone($zone);
        }
        foreach ($zone->getTransitions(1_700_000_000, 1_770_000_000) as $transition) {
            for ($minute = -120; $minute <= 120; $minute += 30) {
                $dates[] = new DateTime('@' . ($transition['ts'] + $minute * 60))->setTimezone($zone);
            }
        }
    }

    $describe = function (object $cron, DateTime $date): array {
        $result = [$cron->isDue($date)];
        foreach ([false, true] as $invert) {
            try {
                $result[] = ($invert ? $cron->getPreviousRunDate($date) : $cron->getNextRunDate($date))->format('c');
            } catch (RuntimeException $e) {
                $result[] = $e->getMessage();
            }
        }

        return $result;
    };

    $differ = [];
    foreach ($expressions as $expression) {
        $fork     = new CronExpression($expression);
        $original = new Cron\CronExpression($expression);
        foreach ($dates as $date) {
            if ($describe($fork, $date) !== $describe($original, $date)) {
                $differ[] = "$expression @ {$date->format('c e')}";
            }
        }
    }
    check('matches dragonmantank/cron-expression on ' . count($expressions) * count($dates) . ' dates, DST included' . ($differ ? ': ' . implode(', ', array_slice($differ, 0, 3)) : ''), $differ === []);
}

// intervals
$job = new Job(fn () => null);
check('everyMinute', expression($job->everyMinute()) === '* * * * *' && expression($job->everyMinute(5)) === '*/5 * * * *');
check('hourly', expression($job->hourly(15)) === '15 * * * *');
check('daily accepts H:i with leading zeros', expression($job->daily('09:05')) === '5 9 * * *' && expression($job->daily(22, 30)) === '30 22 * * *');
check('daily keeps the minute of a * hour', expression($job->daily('*', 30)) === '30 * * * *');
check('weekly and weekdays', expression($job->weekly(3, '10:30')) === '30 10 * * 3' && expression($job->friday(18)) === '0 18 * * 5' && expression($job->sunday()) === '0 0 * * 0');
check('monthly and months', expression($job->monthly(day: 15, hour: '12:45')) === '45 12 15 * *' && expression($job->december(25, 8)) === '0 8 25 12 *');
check('invalid interval values throw', throws(fn () => $job->hourly(60), SchedulerException::class) && throws(fn () => $job->daily('25:00'), SchedulerException::class) && throws(fn () => $job->everyMinute(0), SchedulerException::class) && throws(fn () => $job->weekly(7), SchedulerException::class) && throws(fn () => $job->monthly(13), SchedulerException::class));
check('at accepts an alias', expression($job->at('@hourly')) === '0 * * * *');

$job = new Job(fn () => null)->date('2030-05-06 07:08');
check('date runs once, in its year', $job->isDue(new DateTime('2030-05-06 07:08')) && ! $job->isDue(new DateTime('2031-05-06 07:08')) && ! $job->isDue(new DateTime('2030-05-06 07:09')));
check('at after date clears the year', $job->at('8 7 6 5 *')->isDue(new DateTime('2031-05-06 07:08')));
check('a job without a schedule is due every minute', new Job(fn () => null)->isDue(new DateTime('2025-01-01 03:17')) && expression(new Job(fn () => null)) === '* * * * *');

// jobs
$job = new Job(function (int $a, int $b) {
    echo 'sum:';

    return (string) ($a + $b);
}, ['b' => 2, 'a' => 1]);
check('a closure gets named arguments, the output is its echo plus the returned string', $job->run() && $job->getOutput() === 'sum:3');

$calls = 0;
$job   = new Job(fn () => 'x')->when(function () use (&$calls) {
    $calls++;

    return false;
});
check('when() is checked at the run, not when declared', $calls === 0 && ! $job->run() && $calls === 1 && $job->getOutput() === null);
check('when(true) runs the job', new Job(fn () => 'x')->when(true)->run());

$log = [];
$job = new Job(fn () => 'done', id: 'callbacks')
    ->before(function (Job $job) use (&$log) {
        $log[] = 'before:' . $job->getId();
    })
    ->then(function (?string $output, int $code) use (&$log) {
        $log[] = "then:$output:$code";
    });
$job->run();
check('before gets the job, then gets the output and the exit code', $log === ['before:callbacks', 'then:done:0']);

$file = $tmp . DIRECTORY_SEPARATOR . 'out.txt';
new Job(fn () => 'first')->output($file)->run();
new Job(fn () => 'second')->output($file)->run();
check('output overwrites the file', file_get_contents($file) === 'second');
new Job(fn () => '+third')->output([$file], true)->run();
check('output appends to the file', file_get_contents($file) === 'second+third');

$make = fn () => new Job(fn () => 'x');
check('closure id depends on its location', $make()->getId() === $make()->getId() && $make()->getId() !== new Job(fn () => 'x')->getId());
check('shell id depends on the arguments', new Job('ls', ['-a' => null])->getId() !== new Job('ls', ['-l' => null])->getId());
check('custom id is kept', new Job('ls', id: 'listing')->getId() === 'listing');

$lock = $tmp . DIRECTORY_SEPARATOR . 'lock-job.lock';
$ran  = false;
$job  = new Job(function () use (&$ran, $lock) {
    $ran = is_file($lock);
}, id: 'lock-job')->onlyOne($tmp);
check('onlyOne holds a lock during the run and removes it after', $job->run() && $ran && ! is_file($lock));
touch($lock);
check('onlyOne skips an overlapping run', $job->isOverlapping() && ! $job->run());
$job->onlyOne($tmp, fn (int $mtime) => $mtime <= time());
check('whenOverlapping returning true runs the job anyway', ! $job->isOverlapping() && $job->run());
check('a job without onlyOne never overlaps', ! new Job(fn () => null)->isOverlapping());

$job = new Job(fn () => throw new RuntimeException('boom'), id: 'failing')->onlyOne($tmp);
check('a failing job throws and releases its lock', throws(fn () => $job->run(), RuntimeException::class) && ! is_file($tmp . DIRECTORY_SEPARATOR . 'failing.lock'));
$locked = false;
new Job(function () use (&$locked, $tmp) {
    $locked = is_file($tmp . DIRECTORY_SEPARATOR . 'configured.lock');
}, id: 'configured')->configure(['tempDir' => $tmp])->onlyOne('/no/such/dir')->run();
check('onlyOne falls back to the configured tempDir', $locked);
check('invalid config throws', throws(fn () => new Job('ls')->configure(['email' => 'me@example.com']), SchedulerException::class) && throws(fn () => new Job('ls')->configure(['tempDir' => 1]), SchedulerException::class));

// shell commands
$posix = PHP_OS_FAMILY !== 'Windows';
check('shell arguments are escaped, list arguments have no key', new Job('cmd', ['--env' => 'dev', 'a b', '-v' => null])->inForeground()->compile() === 'cmd ' . escapeshellarg('--env') . ' ' . escapeshellarg('dev') . ' ' . escapeshellarg('a b') . ' ' . escapeshellarg('-v'));
check('closures never run in background', ! new Job(fn () => null)->canRunInBackground());
check('then() and email() force the foreground', ! new Job('ls')->then(fn () => null)->canRunInBackground() && ! new Job('ls')->email('me@example.com')->canRunInBackground());
if ($posix) {
    $job = new Job('ls', id: 'bg')->output(['/tmp/a', '/tmp/b'], true)->onlyOne('/tmp');
    check('background command pipes the output and removes the lock', $job->canRunInBackground() && $job->compile() === "(ls | tee -a '/tmp/a' '/tmp/b'; rm -f '/tmp/bg.lock') > /dev/null 2>&1 &");
} else {
    check('Windows runs shell commands in foreground', ! new Job('ls')->canRunInBackground());
}

$script = $tmp . DIRECTORY_SEPARATOR . 'script.php';
file_put_contents($script, '<?php echo implode(",", array_slice($argv, 1)); exit(3);');

$scheduler = new Scheduler(['tempDir' => $tmp]);
$job       = $scheduler->php($script, null, ['--name' => 'a b', 'c'])->inForeground()->output($file);
$scheduler->run();
check('php() runs a script with escaped arguments', $job->getOutput() === '--name,a b,c' && $job->getReturnCode() === 3);
check('a foreground shell output is written to the files', file_get_contents($file) === "--name,a b,c\n");

$scheduler = new Scheduler();
$job       = $scheduler->php($tmp . DIRECTORY_SEPARATOR . 'missing.php');
check('php() with a missing script fails without being queued', $scheduler->getQueuedJobs() === [] && $scheduler->getFailedJobs()[0]->getJob() === $job);

// scheduler
$scheduler = new Scheduler();
$first     = $scheduler->call(fn () => 'first');
$scheduler->call(fn () => throw new TypeError('type'));
$scheduler->call(fn () => 'skipped')->when(false);
$scheduler->call(fn () => 'not due')->at('0 0 1 1 *');
$last = $scheduler->call('strtoupper', ['last']);

$executed = $scheduler->run(new DateTime('2025-06-15 10:30'));
check('run executes the due jobs, skipped ones are not executed', $executed === [$first, $last] && $last->getOutput() === 'LAST');
check('an Error of a job does not stop the others', count($scheduler->getFailedJobs()) === 1 && $scheduler->getFailedJobs()[0]->getException() instanceof TypeError);
check('verbose output logs every executed and failed job', count($scheduler->getVerboseOutput('array')) === 3 && str_contains($scheduler->getVerboseOutput(), 'type: Closure ' . __FILE__));
check('unknown verbose output type throws', throws(fn () => $scheduler->getVerboseOutput('json'), SchedulerException::class));
check('resetRun clears the results', $scheduler->resetRun()->getExecutedJobs() === [] && $scheduler->getFailedJobs() === [] && $scheduler->getVerboseOutput() === '');
check('clearJobs empties the queue', $scheduler->clearJobs()->getQueuedJobs() === []);

$scheduler = new Scheduler();
$scheduler->call(fn () => throw new Exception('<b>'));
$scheduler->run();
check('html verbose output escapes messages', str_contains($scheduler->getVerboseOutput('html'), '&lt;b&gt;'));

$scheduler  = new Scheduler();
$foreground = $scheduler->call(fn () => null);
$background = $scheduler->raw('ls');
check('background jobs are queued first', $scheduler->getQueuedJobs() === ($posix ? [$background, $foreground] : [$foreground, $background]));
check('work() rejects invalid seconds', throws(fn () => $scheduler->work([60]), SchedulerException::class));

// email
class_exists(Mailer::class);

class FakeMailer extends PHPMailer
{
    public function send(): bool
    {
        return true;
    }
}

$sent = [];
Hook::add('expansaConfigureMailer', function (PHPMailer $mailer) use (&$sent) {
    $sent[] = [$mailer->Subject, $mailer->Body, count($mailer->getAttachments())];

    return new FakeMailer();
});
new Job(fn () => 'report')->email('me@example.com')->configure(['email' => ['subject' => 'Daily']])->output($file)->run();
new Job(fn () => '')->email(['me@example.com'])->configure(['email' => ['ignore_empty_output' => true]])->run();
check('email sends the output with the output files, empty output is ignored if configured', $sent === [['Daily', 'report', 1]]);

// artisan schedule:run, in a separate process: the command writes to STDOUT and exits with the failure code
$command = $tmp . DIRECTORY_SEPARATOR . 'command.php';
file_put_contents($command, '<?php
declare(strict_types=1);
const EX_PATH    = ' . var_export(EX_PATH, true) . ';
const EX_STORAGE = ' . var_export($tmp . DIRECTORY_SEPARATOR, true) . ';
require EX_PATH . "autoload.php";
require EX_PATH . "expansa/functions.php";
Expansa\Facades\Hook::add("schedule", function (Expansa\Scheduler\Scheduler $scheduler) use ($argv) {
    $scheduler->call(fn () => file_put_contents(EX_STORAGE . "ran.txt", "yes"))->onlyOne();
    $scheduler->call(fn () => null)->at("0 0 1 1 *");
    if (isset($argv[1])) {
        $scheduler->call(fn () => throw new RuntimeException("broken job"));
    }
});
new Expansa\Console\Terminal()->run("schedule:run");
');

exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($command) . ' 2>&1', $output, $code);
check('schedule:run runs the jobs of the schedule hook', $code === 0 && count($output) === 1 && str_contains($output[0], 'Done') && str_contains($output[0], 'command.php:8') && is_file($tmp . DIRECTORY_SEPARATOR . 'ran.txt'));
check('schedule:run keeps lock files in the storage', glob($tmp . DIRECTORY_SEPARATOR . '*.lock') === []);

$output = [];
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($command) . ' fail 2>&1', $output, $code);
check('schedule:run reports a failed job and exits with 1', $code === 1 && str_contains(implode("\n", $output), 'broken job'));

array_map('unlink', glob($tmp . DIRECTORY_SEPARATOR . '*'));
rmdir($tmp);

exit($failures > 0 ? 1 : 0);
