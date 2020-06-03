<?php
namespace GarryDzeng\Route {

  use Brick\VarExporter\ExportException;
  use Brick\VarExporter\VarExporter;

  /**
   * @inheritDoc
   */
  class Serializer implements Contract\Serializer {

    /**
     * @inheritDoc
     * @throws
     */
    public function persist($state, $pathname) {

      if (!$state) {
        return;
      }

      // Convert error as exception when calling to internal function
      // they doesn't throws if failed
      set_error_handler(
        function($no, $message) {
          throw new PersistException($message);
        }
      );

      try {

        /*
         * Export as executable PHP code,
         * it retrieved very fast,
         * much faster than unserializing data using unserialize() or json_decode()
         */
        file_put_contents($pathname, "<?php\n".VarExporter::export($state, VarExporter::ADD_RETURN));
      }
      catch (ExportException $exportException) {
        throw new PersistException(
          'Persist failed, '.
          ''.
          'please check.'
        );
      }
      finally {
        restore_error_handler();
      }
    }
  }
}