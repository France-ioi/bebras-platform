module Castor

	ConfigDefaults = {
		timeout: 600,
		worker_processes: 1,
		listeners: [],
		logging: false,
	}

	def self.init
		@blocks = Array.new
		@options = ConfigDefaults.clone
		@options[:root_dir] = File.dirname(__dir__)
		config_file = File.join(@options[:root_dir], 'config.yaml')
		OptionParser.new do |opts|
			opts.on('--config=PATH') do |val|
				config_file = val
			end
			opts.parse!
		end
		config = YAML.load(File.read(config_file))
		@options.each_key do |key|
			if val = config[key.to_s]
				@options[key] = val
			end
		end
		set(:error_log, File.join(self.root_dir, 'errors.json'))
		puts "Error log: #{get(:error_log)}"
	end

	def self.[](name)
		@options[name]
	end

	def self.get(name)
		@options[name]
	end

	def self.set(name, value)
		@options[name] = value
	end

	def self.root_dir
		get(:root_dir)
	end

	def self.write_pidfile
		@pidfile = File.join(Castor.root_dir, '.pid')
		at_exit do
			File.unlink(@pidfile) rescue nil
		end
		File.write(@pidfile, Process.pid.to_s)
	end

end

