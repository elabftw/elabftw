// Run with: go test invoker.go invoker_test.go
package main

import (
	"bytes"
	"errors"
	"io"
	"log"
	"net"
	"os"
	"path/filepath"
	"reflect"
	"strings"
	"sync"
	"syscall"
	"testing"
	"time"
)

func TestParseCommandAllowsExportByID(t *testing.T) {
	for _, id := range []string{"1", "42", "9223372036854775807"} {
		args, err := parseCommand("secret|export:process "+id, "secret")
		if err != nil {
			t.Fatal(err)
		}
		if !reflect.DeepEqual(args, []string{"export:process", id}) {
			t.Fatalf("unexpected arguments: %q", args)
		}
	}
}

func TestParseCommandRejectsOtherCommandsAndArguments(t *testing.T) {
	for _, command := range []string{
		"", "db:update", "import:eln /tmp/archive.eln", "prune:uploads", "snap:fingers",
		"notifications:send", "webhooks:send", "export:other 1", "export:process-all 1",
		"--env=dev export:process 1", "export:process", "export:process 1 2",
		"export:process 1 --env=dev", "export:process --help", "export:process -- 1",
		"export:process 0", "export:process -1", "export:process +1", "export:process 01",
		"export:process 1.0", "export:process 1;db:update", "export:process 1|db:update",
		"export:process 9223372036854775808", "export:process 1\ndb:update",
	} {
		t.Run(command, func(t *testing.T) {
			if args, err := parseCommand("secret|"+command, "secret"); err == nil || args != nil {
				t.Fatalf("accepted forbidden command %q: %q", command, args)
			}
		})
	}
}

func TestParseCommandRequiresPSK(t *testing.T) {
	for _, message := range []string{
		"export:process 1", "|export:process 1", "wrong|export:process 1",
		"secrex|export:process 1", "secre|export:process 1", "secret-extra|export:process 1",
	} {
		if args, err := parseCommand(message, "secret"); err == nil || args != nil {
			t.Fatalf("accepted unauthenticated message %q", message)
		}
	}
	if _, err := parseCommand("|export:process 1", ""); err == nil {
		t.Fatal("accepted an unset PSK")
	}
}

func TestListenUnixRestrictsSocket(t *testing.T) {
	previousMask := syscall.Umask(0)
	t.Cleanup(func() { syscall.Umask(previousMask) })
	socketPath := filepath.Join(t.TempDir(), "invoker.sock")
	listener, err := listenUnix(socketPath)
	if err != nil {
		var syscallErr *os.SyscallError
		if errors.As(err, &syscallErr) && syscallErr.Syscall == "socket" && errors.Is(err, syscall.EPERM) {
			t.Skipf("Unix sockets are unavailable in this environment: %v", err)
		}
		t.Fatal(err)
	}
	t.Cleanup(func() { listener.Close() })
	info, err := os.Stat(socketPath)
	if err != nil {
		t.Fatal(err)
	}
	if info.Mode()&os.ModeSocket == 0 || info.Mode().Perm() != 0600 {
		t.Fatalf("unexpected socket mode: %v", info.Mode())
	}
	if mask := syscall.Umask(0); mask != 0 {
		t.Fatalf("umask was not restored: %#o", mask)
	}
	if listener, err := listenUnix(filepath.Join(socketPath, "invalid.sock")); err == nil || listener != nil {
		t.Fatal("expected a listen failure")
	}
	if mask := syscall.Umask(0); mask != 0 {
		t.Fatalf("umask was not restored after a listen failure: %#o", mask)
	}
}

func TestHandleConnectionRejectsWithoutLoggingSecrets(t *testing.T) {
	const psk = "test-psk-not-for-logs"
	for _, message := range []string{
		"wrong|export:process 1",
		psk + "|db:update",
		psk + "|export:process 1 --help",
		psk + "|export:process",
	} {
		func() {
			var logs bytes.Buffer
			previousWriter := log.Writer()
			log.SetOutput(&logs)
			defer log.SetOutput(previousWriter)
			server, client := net.Pipe()
			defer server.Close()
			defer client.Close()
			deadline := time.Now().Add(5 * time.Second)
			server.SetDeadline(deadline)
			client.SetDeadline(deadline)
			done := make(chan struct{})
			go func() {
				defer close(done)
				handleConnection(server, &sync.Mutex{}, psk)
			}()
			if _, err := io.WriteString(client, message+"\n"); err != nil {
				t.Fatal(err)
			}
			response, err := io.ReadAll(client)
			if err != nil {
				t.Fatal(err)
			}
			<-done
			if string(response) != "error: command rejected\n" {
				t.Fatalf("unexpected response: %q", response)
			}
			if !strings.Contains(logs.String(), "rejected command:") || strings.Contains(logs.String(), psk) {
				t.Fatalf("unexpected log: %q", logs.String())
			}
		}()
	}
}
