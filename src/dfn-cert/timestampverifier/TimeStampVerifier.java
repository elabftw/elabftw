package de.dfncert.timestampverifier;

import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.security.GeneralSecurityException;
import java.security.InvalidKeyException;
import java.security.NoSuchAlgorithmException;
import java.security.NoSuchProviderException;
import java.security.SignatureException;
import java.security.cert.CertPathBuilder;
import java.security.cert.CertPathBuilderException;
import java.security.cert.CertStore;
import java.security.cert.CertificateException;
import java.security.cert.CertificateFactory;
import java.security.cert.CollectionCertStoreParameters;
import java.security.cert.PKIXBuilderParameters;
import java.security.cert.PKIXCertPathBuilderResult;
import java.security.cert.TrustAnchor;
import java.security.cert.X509CRL;
import java.security.cert.X509CertSelector;
import java.security.cert.X509Certificate;
import java.util.Collection;
import java.util.HashSet;
import org.bouncycastle.cert.X509CertificateHolder;
import org.bouncycastle.cert.jcajce.JcaX509CertificateConverter;
import org.bouncycastle.cms.SignerId;
import org.bouncycastle.cms.SignerInformationVerifier;
import org.bouncycastle.cms.jcajce.JcaSimpleSignerInfoVerifierBuilder;
import org.bouncycastle.jce.provider.BouncyCastleProvider;
import org.bouncycastle.operator.OperatorCreationException;
import org.bouncycastle.tsp.TSPException;
import org.bouncycastle.tsp.TSPValidationException;
import org.bouncycastle.tsp.TimeStampRequest;
import org.bouncycastle.tsp.TimeStampResponse;
import org.bouncycastle.tsp.TimeStampToken;
import org.bouncycastle.util.Store;

/**
 * Verifiziert eine Antwort vom Zeitstempelserver.
 *
 * @author nielsen
 */
public class TimeStampVerifier {

    private static final BouncyCastleProvider BC = new BouncyCastleProvider();

    public static void main(String[] args) throws FileNotFoundException,
            IOException,
            TSPException,
            CertificateException,
            OperatorCreationException,
            NoSuchAlgorithmException,
            InvalidKeyException,
            NoSuchProviderException,
            SignatureException,
            CertPathBuilderException,
            GeneralSecurityException {

        if (args.length < 2) {
            System.err.println("Usage: java TimeStampVerifier request response"
                    + " [chain trustedroots crls]");
            System.exit(1);
        }

        FileInputStream requestStream = new FileInputStream(args[0]);
        FileInputStream responseStream = new FileInputStream(args[1]);

        // Part 1: Matching of request and response
        // ---------------------------------------------------------------------
        TimeStampRequest request = new TimeStampRequest(requestStream);
        TimeStampResponse response = new TimeStampResponse(responseStream);
        try {
            response.validate(request);
        } catch (TSPValidationException ex) {
            System.err.println("Response could not be validated: "
                    + ex.getMessage());
            System.exit(2);
        }

        System.out.println("Data in response matches data in request.");

        // Part 2: Verification of the signature
        // ---------------------------------------------------------------------
        TimeStampToken timeStampToken = response.getTimeStampToken();

        // Get the tsa-certificate from the response
        SignerId signerID = timeStampToken.getSID();
        Store allCertificates = timeStampToken.getCertificates();
        Collection signerCertificates = allCertificates.getMatches(signerID);

        if (signerCertificates.isEmpty()) {
            // Response without certificate
            System.err.println("No signer certificate in response. If you are"
                    + " using openssl for request generation try -cert.");
            System.exit(3);
        }

        X509CertificateHolder certHolder = null;
        for (Object match : signerCertificates) {
            certHolder = (X509CertificateHolder) match;
            break;
        }
        assert certHolder != null : "certHolder can't be null because the "
                + "collection is not empty.";

        X509Certificate tsaCert = new JcaX509CertificateConverter()
                .setProvider(BC).getCertificate(certHolder);

        System.out.println("Signer: " + tsaCert.getSubjectDN());

        SignerInformationVerifier siv = new JcaSimpleSignerInfoVerifierBuilder()
                .setProvider(BC).build(tsaCert);

        // Validate using the certificate extracted from the response
        try {
            timeStampToken.validate(siv);
        } catch (TSPValidationException ex) {
            System.err.println("Time stamp token could not be validated: "
                    + ex.getMessage());
            System.exit(4);
        }

        System.out.println("Time stamp token validated.");

        // Part 3: Verification of the tsa-certificate
        // ---------------------------------------------------------------------
        if (args.length < 5) {
            System.out.println("Certificate Chain not validated.");
            return; // Stop without chain verification
        }
        
        // Read chain, trusted roots and crls from files
        FileInputStream chainStream = new FileInputStream(args[2]);
        FileInputStream rootStream = new FileInputStream(args[3]);
        FileInputStream crlStream = new FileInputStream(args[4]);

        CertificateFactory cf = CertificateFactory.getInstance("X.509");

        Collection<X509Certificate> chainCerts
                = (Collection<X509Certificate>) cf.generateCertificates(
                        chainStream);
        Collection<X509Certificate> trustedRoots
                = (Collection<X509Certificate>) cf.generateCertificates(
                        rootStream);
        Collection<X509CRL> crls
                = (Collection<X509CRL>) cf.generateCRLs(crlStream);

        for (X509CRL crl : crls) {
            System.out.println("CRL from issuer " + crl.getIssuerDN()
                    + " thisUpdate: " + crl.getThisUpdate());
        }

        PKIXCertPathBuilderResult result
                = buildPath(tsaCert, trustedRoots, chainCerts, crls);

        String trustAnchor = result.getTrustAnchor().getTrustedCert()
                .getSubjectDN().getName();

        System.out.println("CertPath validated. TrustAnchor: " + trustAnchor);
    }

    /**
     * Build a validation path for a given certificate.
     *
     * @param cert the certificate
     * @param rootCerts trusted root certificates
     * @param intermediateCerts intermediate certificates to build the path from
     * @param crls all crls needed for revocation checking
     * @return successful result containing the path
     * @throws GeneralSecurityException
     */
    private static PKIXCertPathBuilderResult buildPath(X509Certificate cert,
            Collection<X509Certificate> rootCerts,
            Collection<X509Certificate> intermediateCerts,
            Collection<X509CRL> crls)
            throws GeneralSecurityException {

        X509CertSelector selector = new X509CertSelector();
        selector.setCertificate(cert);

        HashSet<TrustAnchor> rootSet = new HashSet<>();
        for (X509Certificate rootCert : rootCerts) {
            rootSet.add(new TrustAnchor(rootCert, null));
        }
        CertPathBuilder builder = CertPathBuilder.getInstance("PKIX", "SUN");

        PKIXBuilderParameters buildParams;
        buildParams = new PKIXBuilderParameters(rootSet, selector);

        CertStore intermediateStore = CertStore.getInstance("Collection",
                new CollectionCertStoreParameters(intermediateCerts));
        CertStore crlStore = CertStore.getInstance("Collection",
                new CollectionCertStoreParameters(crls));

        buildParams.addCertStore(intermediateStore);
        buildParams.addCertStore(crlStore);
        buildParams.setRevocationEnabled(true);

        return (PKIXCertPathBuilderResult) builder.build(buildParams);
    }
}
