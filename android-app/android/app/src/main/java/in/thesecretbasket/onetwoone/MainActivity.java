package in.thesecretbasket.onetwoone;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.os.SystemClock;
import android.view.View;
import android.view.WindowManager;
import android.webkit.WebSettings;
import android.webkit.WebView;

import androidx.annotation.NonNull;
import androidx.biometric.BiometricManager;
import androidx.biometric.BiometricPrompt;
import androidx.core.content.ContextCompat;

import com.getcapacitor.BridgeActivity;

/**
 * Hardened shell around the 1two1 website.
 *
 *  - FLAG_SECURE: no screenshots, no screen recording, and the chat is blanked in the recent-apps switcher.
 *  - App lock: after the app has been in the background for LOCK_AFTER_MS it asks for the phone's
 *    fingerprint / face / PIN before showing the chat again. Native only: no JavaScript can switch it off.
 *  - WebView hardening: no file or content access, no geolocation, safe browsing on, autofill off.
 *  - Navigation is limited to our own site (capacitor.config.json allowNavigation); other links open in the browser.
 */
public class MainActivity extends BridgeActivity {

    private static final long LOCK_AFTER_MS = 60_000L;

    private long leftAt = 0L;
    private boolean locked = false;
    private boolean prompting = false;

    @Override
    public void onCreate(Bundle savedInstanceState) {
        // Release builds block screenshots. Only a debug build (never shipped) allows them, for testing the UI.
        if (!BuildConfig.DEBUG) {
            getWindow().setFlags(WindowManager.LayoutParams.FLAG_SECURE, WindowManager.LayoutParams.FLAG_SECURE);
        }
        super.onCreate(savedInstanceState);
        hardenWebView();
    }

    private void hardenWebView() {
        WebView web = getBridge().getWebView();
        WebSettings s = web.getSettings();
        s.setAllowFileAccess(false);
        s.setAllowContentAccess(false);
        s.setAllowFileAccessFromFileURLs(false);
        s.setAllowUniversalAccessFromFileURLs(false);
        s.setGeolocationEnabled(false);
        s.setSafeBrowsingEnabled(true);
        s.setSupportMultipleWindows(false);
        s.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        web.setImportantForAutofill(View.IMPORTANT_FOR_AUTOFILL_NO_EXCLUDE_DESCENDANTS);
        web.setLongClickable(true);

        // The "Update available" notice links to the new APK. A WebView cannot download files itself, so hand
        // that one link (and only links under our own /downloads/ folder) to the phone's browser.
        web.setDownloadListener((url, userAgent, contentDisposition, mimetype, contentLength) -> {
            if (url != null && url.startsWith("https://1two1.thesecretbasket.in/downloads/")) {
                try {
                    startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(url)));
                } catch (Exception ignored) {
                    // no browser installed: nothing to do
                }
            }
        });
    }

    @Override
    public void onStop() {
        leftAt = SystemClock.elapsedRealtime();
        super.onStop();
    }

    @Override
    public void onStart() {
        super.onStart();
        boolean away = leftAt != 0L && SystemClock.elapsedRealtime() - leftAt > LOCK_AFTER_MS;
        if ((away || locked) && canAuthenticate()) {
            lock();
        }
    }

    private boolean canAuthenticate() {
        int authenticators = BiometricManager.Authenticators.BIOMETRIC_STRONG | BiometricManager.Authenticators.DEVICE_CREDENTIAL;
        return BiometricManager.from(this).canAuthenticate(authenticators) == BiometricManager.BIOMETRIC_SUCCESS;
    }

    /** Hide the chat and ask the phone to confirm it is really the owner. */
    private void lock() {
        locked = true;
        getBridge().getWebView().setVisibility(View.INVISIBLE);
        if (prompting) return;
        prompting = true;

        BiometricPrompt prompt = new BiometricPrompt(this, ContextCompat.getMainExecutor(this),
            new BiometricPrompt.AuthenticationCallback() {
                @Override
                public void onAuthenticationSucceeded(@NonNull BiometricPrompt.AuthenticationResult result) {
                    prompting = false;
                    locked = false;
                    leftAt = 0L;
                    getBridge().getWebView().setVisibility(View.VISIBLE);
                }

                @Override
                public void onAuthenticationError(int errorCode, @NonNull CharSequence errString) {
                    // Cancelled or failed: leave the app rather than show the chat.
                    prompting = false;
                    finishAndRemoveTask();
                }
            });

        BiometricPrompt.PromptInfo info = new BiometricPrompt.PromptInfo.Builder()
            .setTitle("Unlock 1two1")
            .setSubtitle("Confirm it is you to open your private chat")
            .setAllowedAuthenticators(BiometricManager.Authenticators.BIOMETRIC_STRONG | BiometricManager.Authenticators.DEVICE_CREDENTIAL)
            .build();
        prompt.authenticate(info);
    }
}
