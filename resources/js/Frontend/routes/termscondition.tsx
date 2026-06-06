import { createFileRoute } from '@tanstack/react-router';
import React from 'react';

export const Route = createFileRoute('/termscondition')({
  component: TermsConditionComponent,
});

function TermsConditionComponent() {
  return (
    <div className="container mx-auto px-4 py-12 max-w-4xl text-foreground bg-background">
      <h1 className="text-3xl font-bold mb-6 text-primary">Terms and Conditions</h1>
      <div className="prose prose-slate dark:prose-invert max-w-none">
        <p className="text-sm text-muted-foreground mb-8">Last updated: {new Date().toLocaleDateString()}</p>

        <p className="mb-4">
          Please read these Terms and Conditions carefully before using the Scrapify website and mobile application 
          operated by us. Your access to and use of the Service is conditioned on your acceptance of and compliance 
          with these Terms. These Terms apply to all visitors, users and others who access or use the Service.
        </p>

        <h2 className="text-xl font-semibold mt-8 mb-4">1. Acceptance of Terms</h2>
        <p className="mb-4">
          By accessing or using the Service you agree to be bound by these Terms. If you disagree with any part of the terms then you may not access the Service.
        </p>

        <h2 className="text-xl font-semibold mt-8 mb-4">2. Service Description</h2>
        <p className="mb-4">
          Scrapify provides a platform to schedule scrap pickups, manage recycling needs, and connect users with authorized channel partners and pickup boys. We reserve the right to withdraw or amend our service, and any service or material we provide via the platform, in our sole discretion without notice.
        </p>

        <h2 className="text-xl font-semibold mt-8 mb-4">3. Accounts</h2>
        <p className="mb-4">
          When you create an account with us, you must provide us information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our Service.
        </p>
        <p className="mb-4">
          You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your password, whether your password is with our Service or a third-party service.
        </p>

        <h2 className="text-xl font-semibold mt-8 mb-4">4. Intellectual Property</h2>
        <p className="mb-4">
          The Service and its original content, features and functionality are and will remain the exclusive property of Scrapify and its licensors. The Service is protected by copyright, trademark, and other laws of both the country and foreign countries.
        </p>

        <h2 className="text-xl font-semibold mt-8 mb-4">5. Termination</h2>
        <p className="mb-4">
          We may terminate or suspend access to our Service immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms. All provisions of the Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity and limitations of liability.
        </p>

        <h2 className="text-xl font-semibold mt-8 mb-4">6. Changes</h2>
        <p className="mb-4">
          We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material we will try to provide at least 30 days notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.
        </p>

        <h2 className="text-xl font-semibold mt-8 mb-4">7. Contact Us</h2>
        <p className="mb-4">
          If you have any questions about these Terms, please contact us at support@scrapi5.com.
        </p>
        <p className="mb-4">
          <strong>Office Address:</strong> E-44/3 Okhla Industrial Area Phase - 2, New Delhi - 110020<br />
          <strong>Landline:</strong> +91 11 3574 8627<br />
          <strong>Mobile:</strong> +91 98702 91813
        </p>
      </div>
    </div>
  );
}
