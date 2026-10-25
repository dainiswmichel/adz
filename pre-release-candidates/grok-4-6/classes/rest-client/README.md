**Generalized REST client**

It's often necessary for PHP apps to consume web services from a variety of sources.

In my experience these web services have had different have different types of
authentication (Basic, Digest, OAuth, OAuth2, Custom Headers or sometimes a combination)

Furthermore, depending on the framework, server or app environment, the underlying
http library may be different.

I needed a generalized REST library that I could use regardless of those factors.  

So, I created an interface that I could rely on that exposed the essentials of every
REST interface I've had to use.

I then created two sample implentations, one using CURL, the other using WordPress's
http library.

Feel free to learn from, use or modify them as you see fit.
