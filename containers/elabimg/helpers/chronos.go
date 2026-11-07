// This program is like chronie: doing scheduled tasks
// © 2025 Nicolas CARPi
// License: AGPLv3
package main

import (
	"context"
	"errors"
	"log"
	"math/rand/v2"
	"os"
	"os/exec"
	"os/signal"
	"strings"
	"sync"
	"syscall"
	"time"
)

// Each run gets a deadline, so a stuck command cannot block its schedule forever.
// Runs of the same task never overlap: a task waits for its previous run to finish.
const (
	minuteTaskTimeout = 15 * time.Minute
	dailyTaskTimeout  = 2 * time.Hour
)

var (
	// jitter to avoid all tasks executing at the exact same second
	jitter time.Duration
	// a command that does not stop after SIGTERM is killed after this delay
	killDelay = 30 * time.Second
	// variables so tests can replace them
	phpBinary   = "/usr/bin/php"
	consolePath = "/elabftw/bin/console"
)

func main() {
	log.SetPrefix("chronos: ")
	log.Println("starting chronos...")
	// generate jitter
	jitter = time.Duration(rand.IntN(60)) * time.Second
	log.Printf("jitter: %v\n", jitter)
	// set up a context that is canceled on SIGINT/SIGTERM
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()
	sigs := make(chan os.Signal, 1)
	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)
	go func() {
		<-sigs
		log.Println("Shutdown signal received")
		cancel()
	}()

	// jobs
	var wg sync.WaitGroup
	wg.Go(func() { scheduleWeekly(ctx, time.Sunday, 1, 45, []string{"notifications:send-expiration"}) })
	wg.Go(func() { scheduleDaily(ctx, 13, 37, []string{"notifications:tsbalance"}) })
	wg.Go(func() { scheduleDaily(ctx, 3, 37, []string{"idps:refresh"}) })
	wg.Go(func() { scheduleEveryMinute(ctx, []string{"notifications:send"}) })
	wg.Go(func() { scheduleEveryMinute(ctx, []string{"webhooks:send"}) })
	log.Println("scheduled tasks started")

	// wait until context is canceled, then for running tasks to stop
	<-ctx.Done()
	wg.Wait()
	log.Println("exiting")
}

// scheduleWeekly runs cmdArgs each week at the given weekday/hour/minute.
func scheduleWeekly(ctx context.Context, weekday time.Weekday, hour, min int, cmdArgs []string) {
	for {
		// computed after the previous run, so it is always the next week
		next := nextWeekdayTime(time.Now(), weekday, hour, min)
		if !waitOrExit(ctx, time.Until(next)+jitter) {
			return
		}
		runPHP(ctx, dailyTaskTimeout, cmdArgs)
	}
}

// scheduleDaily runs cmdArgs each day at the given hour/minute.
func scheduleDaily(ctx context.Context, hour, min int, cmdArgs []string) {
	for {
		// computed after the previous run, so it is always the next day
		next := nextDailyTime(time.Now(), hour, min)
		if !waitOrExit(ctx, time.Until(next)+jitter) {
			return
		}
		runPHP(ctx, dailyTaskTimeout, cmdArgs)
	}
}

// scheduleEveryMinute runs cmdArgs every 1 minute on the minute.
// A run that lasts longer delays the next one: the ticker drops the ticks in between.
func scheduleEveryMinute(ctx context.Context, cmdArgs []string) {
	if !waitOrExit(ctx, jitter) {
		return
	}

	ticker := time.NewTicker(time.Minute)
	defer ticker.Stop()

	for {
		select {
		case <-ticker.C:
			runPHP(ctx, minuteTaskTimeout, cmdArgs)
		case <-ctx.Done():
			return
		}
	}
}

// nextWeekdayTime finds the next occurrence of weekday at hour:min
func nextWeekdayTime(from time.Time, weekday time.Weekday, hour, min int) time.Time {
	// start with today's date at the target time
	candidate := time.Date(from.Year(), from.Month(), from.Day(), hour, min, 0, 0, from.Location())
	// how many days to add until we hit the target weekday?
	daysAhead := (int(weekday) - int(from.Weekday()) + 7) % 7
	if daysAhead == 0 && !candidate.After(from) {
		daysAhead = 7
	}
	return candidate.AddDate(0, 0, daysAhead)
}

// nextDailyTime finds the next occurrence of today-or-tomorrow at hour:min
func nextDailyTime(from time.Time, hour, min int) time.Time {
	candidate := time.Date(from.Year(), from.Month(), from.Day(), hour, min, 0, 0, from.Location())
	if !candidate.After(from) {
		// next calendar day, at the same local time even across a DST change
		candidate = candidate.AddDate(0, 0, 1)
	}
	return candidate
}

// waitOrExit sleeps for d, and returns false if ctx is canceled first
func waitOrExit(ctx context.Context, d time.Duration) bool {
	if ctx.Err() != nil {
		return false
	}
	timer := time.NewTimer(d)
	defer timer.Stop()
	select {
	case <-ctx.Done():
		return false
	case <-timer.C:
		return true
	}
}

// runPHP executes: php /elabftw/bin/console <cmdArgs...>
// The command gets SIGTERM when its timeout expires or chronos stops, then SIGKILL after killDelay.
func runPHP(ctx context.Context, timeout time.Duration, cmdArgs []string) {
	runCtx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()
	args := append([]string{consolePath}, cmdArgs...)
	cmd := exec.CommandContext(runCtx, phpBinary, args...)
	cmd.Cancel = func() error {
		return cmd.Process.Signal(syscall.SIGTERM)
	}
	cmd.WaitDelay = killDelay
	out, err := cmd.CombinedOutput()
	task := strings.Join(cmdArgs, " ")
	if errors.Is(runCtx.Err(), context.DeadlineExceeded) {
		log.Printf("error: %s did not finish within %v", task, timeout)
	}
	if err != nil {
		log.Printf("error: %s: %v — output:\n%s", task, err, string(out))
	}
}
