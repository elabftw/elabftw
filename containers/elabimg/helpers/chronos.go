// This program is like chronie: doing scheduled tasks
// © 2025 Nicolas CARPi
// License: AGPLv3
package main

import (
	"context"
	"log"
	"math/rand"
	"os"
	"os/exec"
	"os/signal"
	"syscall"
	"time"
)

// jitter to avoid all tasks executing at the exact same second
var jitter time.Duration

func main() {
	log.SetPrefix("chronos: ")
	log.Println("starting chronos...")
	// generate jitter
	rand.Seed(time.Now().UnixNano())
	jitter = time.Duration(rand.Intn(60)) * time.Second
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
	go scheduleWeekly(ctx, time.Sunday, 1, 45, []string{"notifications:send-expiration"})
	go scheduleDaily(ctx, 13, 37, []string{"notifications:tsbalance"})
	go scheduleDaily(ctx, 3, 37, []string{"idps:refresh"})
	go scheduleEveryMinute(ctx, []string{"notifications:send"})
	log.Println("scheduled tasks started")

	// wait until context is canceled
	<-ctx.Done()
	log.Println("exiting")
}

// scheduleWeekly runs cmdArgs each week at the given weekday/hour/minute.
func scheduleWeekly(ctx context.Context, weekday time.Weekday, hour, min int, cmdArgs []string) {
	for {
		next := nextWeekdayTime(time.Now(), weekday, hour, min)
		waitOrExit(ctx, time.Until(next)+jitter)
		runPHP(next, cmdArgs)
		// after first run, interval is always 7 days
		waitOrExit(ctx, 7*24*time.Hour)
	}
}

// scheduleDaily runs cmdArgs each day at the given hour/minute.
func scheduleDaily(ctx context.Context, hour, min int, cmdArgs []string) {
	for {
		next := nextDailyTime(time.Now(), hour, min)
		waitOrExit(ctx, time.Until(next)+jitter)
		runPHP(next, cmdArgs)
		// after first run, interval is always 24h
		waitOrExit(ctx, 24*time.Hour)
	}
}

// scheduleEveryMinute runs cmdArgs every 1 minute on the minute.
func scheduleEveryMinute(ctx context.Context, cmdArgs []string) {
	waitOrExit(ctx, jitter)

	ticker := time.NewTicker(time.Minute)
	defer ticker.Stop()

	for {
		select {
		case t := <-ticker.C:
			runPHP(t, cmdArgs)
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
		candidate = candidate.Add(24 * time.Hour)
	}
	return candidate
}

// waitOrExit sleeps for d or returns early if ctx is canceled
func waitOrExit(ctx context.Context, d time.Duration) {
	timer := time.NewTimer(d)
	select {
	case <-ctx.Done():
		timer.Stop()
	case <-timer.C:
	}
}

// runPHP executes: php /elabftw/bin/console <cmdArgs...>
func runPHP(t time.Time, cmdArgs []string) {
	args := append([]string{"/elabftw/bin/console"}, cmdArgs...)
	cmd := exec.Command("/usr/bin/php", args...)
	out, err := cmd.CombinedOutput()
	if err != nil {
		log.Printf("error: %v — output:\n%s", err, string(out))
	}
}
