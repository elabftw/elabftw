package main

import (
	"context"
	"os"
	"path/filepath"
	"testing"
	"time"
	_ "time/tzdata"
)

// useScript makes runPHP execute a shell script instead of bin/console
func useScript(t *testing.T, script string) string {
	t.Helper()
	dir := t.TempDir()
	path := filepath.Join(dir, "console")
	if err := os.WriteFile(path, []byte(script), 0o600); err != nil {
		t.Fatal(err)
	}
	oldBinary, oldConsole, oldDelay := phpBinary, consolePath, killDelay
	phpBinary, consolePath, killDelay = "/bin/sh", path, time.Second
	t.Cleanup(func() { phpBinary, consolePath, killDelay = oldBinary, oldConsole, oldDelay })
	return dir
}

func TestDailyTaskRunsOncePerDay(t *testing.T) {
	loc, err := time.LoadLocation("Europe/Paris")
	if err != nil {
		t.Fatal(err)
	}
	next := nextDailyTime(time.Date(2026, 6, 1, 12, 0, 0, 0, loc), 3, 37)
	// the run starts after the jitter and takes a while
	runEnd := next.Add(59*time.Second + 10*time.Minute)
	if got, want := nextDailyTime(runEnd, 3, 37), next.AddDate(0, 0, 1); !got.Equal(want) {
		t.Fatalf("next run after %v: got %v, want %v", runEnd, got, want)
	}
}

func TestWeeklyTaskRunsOncePerWeek(t *testing.T) {
	next := nextWeekdayTime(time.Date(2026, 6, 1, 12, 0, 0, 0, time.UTC), time.Sunday, 1, 45)
	if next.Weekday() != time.Sunday {
		t.Fatalf("got %v, want a Sunday", next)
	}
	runEnd := next.Add(59*time.Second + 10*time.Minute)
	if got, want := nextWeekdayTime(runEnd, time.Sunday, 1, 45), next.AddDate(0, 0, 7); !got.Equal(want) {
		t.Fatalf("next run after %v: got %v, want %v", runEnd, got, want)
	}
}

func TestNextDailyTimeKeepsLocalTimeAcrossDST(t *testing.T) {
	loc, err := time.LoadLocation("Europe/Paris")
	if err != nil {
		t.Fatal(err)
	}
	// clocks go forward on 2026-03-29: the day only has 23 hours
	got := nextDailyTime(time.Date(2026, 3, 28, 4, 0, 0, 0, loc), 3, 37)
	if want := time.Date(2026, 3, 29, 3, 37, 0, 0, loc); !got.Equal(want) {
		t.Fatalf("got %v, want %v", got, want)
	}
}

func TestRunPHPStopsAfterTimeout(t *testing.T) {
	useScript(t, "exec sleep 30\n")
	start := time.Now()
	runPHP(context.Background(), 200*time.Millisecond, nil)
	if elapsed := time.Since(start); elapsed > 5*time.Second {
		t.Fatalf("runPHP returned after %v", elapsed)
	}
}

func TestRunPHPKillsCommandIgnoringSIGTERM(t *testing.T) {
	useScript(t, "trap '' TERM\nsleep 30\n")
	start := time.Now()
	runPHP(context.Background(), 200*time.Millisecond, nil)
	// timeout, then killDelay before SIGKILL
	if elapsed := time.Since(start); elapsed > 5*time.Second {
		t.Fatalf("runPHP returned after %v", elapsed)
	}
}

func TestRunPHPStopsOnShutdown(t *testing.T) {
	useScript(t, "exec sleep 30\n")
	ctx, cancel := context.WithCancel(context.Background())
	time.AfterFunc(200*time.Millisecond, cancel)
	start := time.Now()
	runPHP(ctx, time.Hour, nil)
	if elapsed := time.Since(start); elapsed > 5*time.Second {
		t.Fatalf("runPHP returned after %v", elapsed)
	}
}

func TestSchedulesReturnOnShutdownWithoutRunning(t *testing.T) {
	dir := useScript(t, "touch \"$(dirname \"$0\")/ran\"\n")
	ctx, cancel := context.WithCancel(context.Background())
	cancel()
	done := make(chan struct{})
	go func() {
		scheduleDaily(ctx, 3, 37, nil)
		scheduleWeekly(ctx, time.Sunday, 1, 45, nil)
		scheduleEveryMinute(ctx, nil)
		close(done)
	}()
	select {
	case <-done:
	case <-time.After(5 * time.Second):
		t.Fatal("schedules did not return after shutdown")
	}
	if _, err := os.Stat(filepath.Join(dir, "ran")); err == nil {
		t.Fatal("a task ran after shutdown")
	}
}
