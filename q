[1mdiff --git a/src/Controllers/Apiv2Controller.php b/src/Controllers/Apiv2Controller.php[m
[1mindex 7f6615075..c22242b00 100644[m
[1m--- a/src/Controllers/Apiv2Controller.php[m
[1m+++ b/src/Controllers/Apiv2Controller.php[m
[36m@@ -231,6 +231,7 @@[m [mfinal class Apiv2Controller extends AbstractApiController[m
             $this->reqBody['entity_type'] = $this->Request->request->get('entity_type'); // can be null[m
             $this->reqBody['category'] = $this->Request->request->get('category'); // can be null[m
             $this->reqBody['owner'] = $this->Request->request->getInt('owner');[m
[32m+[m[32m            $this->reqBody['resource_template'] = $this->Request->request->getInt('resource_template');[m
             $this->reqBody['canread_base'] = (BasePermissions::tryFrom($this->Request->request->getInt('canread_base')) ?? BasePermissions::Team)->value;[m
             $this->reqBody['canwrite_base'] = (BasePermissions::tryFrom($this->Request->request->getInt('canwrite_base')) ?? BasePermissions::User)->value;[m
             $this->action = Action::tryFrom($this->Request->request->getString('action')) ?? Action::Create;[m
[1mdiff --git a/src/Import/Handler.php b/src/Import/Handler.php[m
[1mindex e0108ed4f..f6ea3f875 100644[m
[1m--- a/src/Import/Handler.php[m
[1m+++ b/src/Import/Handler.php[m
[36m@@ -106,6 +106,7 @@[m [mfinal class Handler extends AbstractRest[m
                     category: (int) $reqBody['category'],[m
                     canreadBase: $canreadBase,[m
                     canwriteBase: $canwriteBase,[m
[32m+[m[32m                    resourceTemplate: empty($reqBody['resource_template']) ? null : (int) $reqBody['resource_template'],[m
                 );[m
             default:[m
                 throw new ImproperActionException(sprintf([m
